import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const scriptPattern = /<script\s+setup\s+lang=(?:"ts"|'ts')\s*>([\s\S]*?)<\/script\s*>/gi;
const stylePattern = /<style\s+scoped\s*>([\s\S]*?)<\/style\s*>/gi;
const pagesPattern = /<pages\s*>([\s\S]*?)<\/pages\s*>/gi;

function filesIn(directory) {
    if (!fs.existsSync(directory)) return [];

    return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const filePath = path.join(directory, entry.name);

        if (entry.isDirectory()) return filesIn(filePath);
        if (entry.isFile() && entry.name.endsWith('.blade.php') && !entry.name.startsWith('_')) return [filePath];

        return [];
    });
}

function capture(source, pattern, filePath, blockName, optional = false) {
    const matches = [...source.matchAll(new RegExp(pattern.source, pattern.flags))];

    if (matches.length === 0 && optional) return '';
    if (matches.length !== 1) throw new Error(`${filePath}: expected ${optional ? 'at most one' : 'exactly one'} ${blockName} block.`);

    return matches[0][1].trim();
}

function prepareScript(source, filePath, scope, ts) {
    const parsed = ts.createSourceFile(filePath, source, ts.ScriptTarget.Latest, true, ts.ScriptKind.TS);

    if (parsed.parseDiagnostics.length > 0) {
        const diagnostic = parsed.parseDiagnostics[0];
        const location = parsed.getLineAndCharacterOfPosition(diagnostic.start ?? 0);
        const message = ts.flattenDiagnosticMessageText(diagnostic.messageText, '\n');
        throw new Error(`${filePath}:${location.line + 1}:${location.character + 1}: ${message}`);
    }

    const imports = [];
    const body = [];

    for (const statement of parsed.statements) {
        if (ts.isImportDeclaration(statement) || ts.isImportEqualsDeclaration(statement)) {
            imports.push(statement.getText(parsed));
            continue;
        }

        if (ts.isExportDeclaration(statement) || ts.isExportAssignment(statement)
            || statement.modifiers?.some((modifier) => modifier.kind === ts.SyntaxKind.ExportKeyword)) {
            throw new Error(`${filePath}: exports are not allowed in <script setup>; imports are supported.`);
        }

        body.push(parsed.text.slice(statement.getFullStart(), statement.end));
    }

    return `import { useSeoMeta } from '../frontend/seo-meta';\n${imports.join('\n')}\nexport async function mount(): Promise<void> {\n    if (!document.querySelector('[data-page-scope="${scope}"]')) return;\n${body.join('\n')}\n}\n`;
}

function pageKey(relativeName) {
    const segments = relativeName.replace(/\.blade\.php$/, '').split('/');
    if (segments.length > 1 && segments.at(-1) === 'index') segments.pop();
    return segments.join('.');
}

function extractSeoMeta(source, filePath, ts) {
    const parsed = ts.createSourceFile(filePath, source, ts.ScriptTarget.Latest, true, ts.ScriptKind.TS);
    const fields = new Set([
        'title', 'description', 'ogTitle', 'ogDescription', 'ogImage', 'ogUrl', 'ogType',
        'twitterTitle', 'twitterDescription', 'twitterImage', 'twitterCard', 'articleAuthor',
    ]);
    const metadata = {};

    const staticString = (expression) => {
        while (ts.isParenthesizedExpression(expression) || ts.isAsExpression(expression)
            || ts.isTypeAssertionExpression(expression) || ts.isSatisfiesExpression(expression)) {
            expression = expression.expression;
        }

        if (ts.isStringLiteralLike(expression) || ts.isNoSubstitutionTemplateLiteral(expression)) return expression.text;
        return undefined;
    };

    const visit = (node) => {
        if (ts.isCallExpression(node) && ts.isIdentifier(node.expression) && node.expression.text === 'useSeoMeta') {
            const argument = node.arguments[0];
            if (argument && ts.isObjectLiteralExpression(argument)) {
                for (const property of argument.properties) {
                    if (!ts.isPropertyAssignment(property)) continue;
                    const name = property.name;
                    const key = ts.isIdentifier(name) || ts.isStringLiteralLike(name) ? name.text : undefined;
                    if (!key || !fields.has(key)) continue;
                    const value = staticString(property.initializer);
                    if (value !== undefined) metadata[key] = value;
                }
            }
        }

        ts.forEachChild(node, visit);
    };

    visit(parsed);
    return metadata;
}

function scopeStyle(source, scope, filePath, postcss) {
    const root = postcss.parse(source, { from: filePath });
    const prefix = `:where([data-page-scope="${scope}"])`;

    root.walkRules((rule) => {
        let parent = rule.parent;
        while (parent && parent !== root) {
            if (parent.type === 'atrule' && /keyframes$/i.test(parent.name)) return;
            parent = parent.parent;
        }

        rule.selectors = rule.selectors.map((selector) => {
            const trimmed = selector.trim();

            if (/^(?:html|body|:root)(?=$|\s|[.#:[>+~])/.test(trimmed)) {
                return `${prefix}${trimmed.replace(/^(?:html|body|:root)/, '')}`;
            }

            return `${prefix} ${trimmed}`;
        });
    });

    return root.toString();
}

function writeIfChanged(filePath, contents) {
    if (fs.existsSync(filePath) && fs.readFileSync(filePath, 'utf8') === contents) return;
    fs.mkdirSync(path.dirname(filePath), { recursive: true });
    fs.writeFileSync(filePath, contents);
}

function generate(root) {
    const requireFromProject = createRequire(path.join(root, 'package.json'));
    const ts = requireFromProject('typescript');
    const postcss = requireFromProject('postcss');
    const pagesDirectory = path.resolve(root, 'src/pages');
    const generatedDirectory = path.resolve(root, '.laravel/generated');
    const modules = [];
    const styles = [':where([data-page-scope]) { display: contents; }'];
    const moduleNames = new Set();
    const seoPages = {};

    for (const filePath of filesIn(pagesDirectory)) {
        const source = fs.readFileSync(filePath, 'utf8');
        if (!/<pages\s*>/i.test(source)) continue;

        const relativeName = path.relative(pagesDirectory, filePath).replaceAll('\\', '/');
        const scripts = capture(source, scriptPattern, relativeName, '<script setup lang="ts">', true);
        const pageMarkup = capture(source, pagesPattern, relativeName, '<pages>');
        const style = capture(source, stylePattern, relativeName, '<style scoped>', true);
        const scope = createHash('sha256').update(source).digest('hex').slice(0, 12);

        const openingScripts = source.match(/<script\b/gi)?.length ?? 0;
        const setupScripts = [...source.matchAll(new RegExp(scriptPattern.source, scriptPattern.flags))].length;
        const openingStyles = source.match(/<style\b/gi)?.length ?? 0;
        const scopedStyles = [...source.matchAll(new RegExp(stylePattern.source, stylePattern.flags))].length;
        const openingPages = source.match(/<pages\s*>/gi)?.length ?? 0;

        if (openingScripts !== setupScripts || setupScripts > 1 || openingStyles !== scopedStyles || scopedStyles > 1 || openingPages !== 1) {
            throw new Error(`${relativeName}: use one <pages> block, with at most one <script setup lang="ts"> block and at most one <style scoped> block.`);
        }

        if (/@extends\s*\(/.test(source)) throw new Error(`${relativeName}: single-file pages use the framework layout automatically; remove @extends.`);
        if (!pageMarkup.trim()) throw new Error(`${relativeName}: <pages> cannot be empty.`);

        if (scripts) {
            const generatedName = `page-${scope}.ts`;
            moduleNames.add(generatedName);
            writeIfChanged(path.join(generatedDirectory, generatedName), prepareScript(scripts, relativeName, scope, ts));
            modules.push({ name: generatedName, scope });
            const metadata = extractSeoMeta(scripts, relativeName, ts);
            if (Object.keys(metadata).length > 0) seoPages[pageKey(relativeName)] = metadata;
        }
        if (style) styles.push(scopeStyle(style, scope, relativeName, postcss));
    }

    for (const entry of fs.existsSync(generatedDirectory) ? fs.readdirSync(generatedDirectory) : []) {
        if (/^page-[a-f0-9]{12}\.ts$/.test(entry) && !moduleNames.has(entry)) {
            fs.unlinkSync(path.join(generatedDirectory, entry));
        }
    }

    const imports = modules.map(({ name }, index) => `import { mount as mountPage${index} } from './${name}';`).join('\n');
    const mounts = modules.map((_, index) => `    await mountPage${index}();`).join('\n');
    writeIfChanged(path.join(generatedDirectory, 'pages.ts'), `${imports}\n\nasync function mountCurrentPage(): Promise<void> {\n${mounts}\n}\n\nvoid mountCurrentPage();\nwindow.addEventListener('auto:navigated', () => { void mountCurrentPage(); });\n`);
    writeIfChanged(path.join(generatedDirectory, 'pages.css'), styles.join('\n\n'));
    writeIfChanged(path.join(generatedDirectory, 'pages-seo.json'), `${JSON.stringify(seoPages, null, 2)}\n`);
}

export function laravelPages() {
    let projectRoot;
    let timer;

    return {
        name: 'davingm-laravel-pages',
        enforce: 'pre',
        config(config) {
            projectRoot = config.root ?? process.cwd();
            generate(projectRoot);
        },
        configureServer(server) {
            projectRoot = server.config.root;
            const pagesDirectory = path.resolve(projectRoot, 'src/pages');
            server.watcher.add(pagesDirectory);

            const refresh = (filePath) => {
                if (!filePath.startsWith(pagesDirectory) || !filePath.endsWith('.blade.php')) return;
                clearTimeout(timer);
                timer = setTimeout(() => {
                    try {
                        generate(projectRoot);
                        server.ws.send({ type: 'full-reload' });
                    } catch (error) {
                        server.config.logger.error(error instanceof Error ? error.message : String(error));
                    }
                }, 30);
            };

            server.watcher.on('add', refresh);
            server.watcher.on('change', refresh);
            server.watcher.on('unlink', refresh);
        },
    };
}

if (process.argv[1] && fs.realpathSync(process.argv[1]) === fs.realpathSync(fileURLToPath(import.meta.url))) {
    generate(process.cwd());
}
