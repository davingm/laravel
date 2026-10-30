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

    return `${imports.join('\n')}\nexport async function mount(): Promise<void> {\n    if (!document.querySelector('[data-page-scope="${scope}"]')) return;\n${body.join('\n')}\n}\n`;
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

    for (const filePath of filesIn(pagesDirectory)) {
        const source = fs.readFileSync(filePath, 'utf8');
        if (!/<pages\s*>/i.test(source)) continue;

        const relativeName = path.relative(pagesDirectory, filePath).replaceAll('\\', '/');
        const scripts = capture(source, scriptPattern, relativeName, '<script setup lang="ts">');
        const pageMarkup = capture(source, pagesPattern, relativeName, '<pages>');
        const style = capture(source, stylePattern, relativeName, '<style scoped>', true);
        const scope = createHash('sha256').update(source).digest('hex').slice(0, 12);

        const openingScripts = source.match(/<script\b/gi)?.length ?? 0;
        const openingStyles = source.match(/<style\b/gi)?.length ?? 0;
        const openingPages = source.match(/<pages\s*>/gi)?.length ?? 0;

        if (openingScripts !== 1 || openingStyles !== (style ? 1 : 0) || openingPages !== 1) {
            throw new Error(`${relativeName}: use one <script setup lang="ts">, one <pages>, and at most one <style scoped> block.`);
        }

        if (/@extends\s*\(/.test(source)) throw new Error(`${relativeName}: single-file pages use the framework layout automatically; remove @extends.`);
        if (!pageMarkup.trim()) throw new Error(`${relativeName}: <pages> cannot be empty.`);

        const generatedName = `page-${scope}.ts`;
        moduleNames.add(generatedName);
        writeIfChanged(path.join(generatedDirectory, generatedName), prepareScript(scripts, relativeName, scope, ts));
        modules.push({ name: generatedName, scope });
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
