#!/usr/bin/env node

/**
 * davingm Laravel CLI
 * .davingm/cli.js
 */

import { spawn, execSync }   from 'node:child_process';
import { resolve, dirname } from 'node:path';
import { fileURLToPath }    from 'node:url';
import { existsSync }       from 'node:fs';

// ─── ANSI ─────────────────────────────────────────────────────────────────────

const R = '\x1b[0m';          // reset
const B = '\x1b[1m';          // bold
const D = '\x1b[2m';          // dim
const RED    = '\x1b[31m';
const ORANGE = '\x1b[38;5;208m';
const CYAN   = '\x1b[36m';
const WHITE  = '\x1b[97m';
const GREEN  = '\x1b[32m';
const GRAY   = '\x1b[90m';

const PRE_RED    = `${B}${RED}[davingm]${R}`;
const PRE_ORANGE = `${B}${ORANGE}[davingm]${R}`;
const PRE_WHITE  = `${B}${WHITE}[davingm]${R}`;

const stripAnsi = (s) => s.replace(/\x1b\[[0-9;]*m/g, '').replace(/\r/g, '');

// ─── Printers ─────────────────────────────────────────────────────────────────

const out = (pre, msg) => process.stdout.write(`${pre} ${msg}\n`);

// ─── ASCII Logo ───────────────────────────────────────────────────────────────

function printLogo() {
    const L = [
        '    __                               __     ',
        '   / /   ____ __________ __   _____  / /    ',
        '  / /   / __ `/ ___/ __ `/ | / / _ \\/ /   ',
        ' / /___/ /_/ / /  / /_/ /| |/ /  __/ /     ',
        '/_____/\\__,_/_/   \\__,_/ |___/\\___/_/   ',
    ];
    process.stdout.write('\n');
    for (const line of L) process.stdout.write(`  ${RED}${B}${line}${R}\n`);
    process.stdout.write('\n');
}

// ─── Project root ─────────────────────────────────────────────────────────────

const __dir      = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(__dir, '..');

// ─── Laravel serve output parser ─────────────────────────────────────────────
//
// `php artisan serve` outputs per-request like:
//
//   2026-09-22 11:13:03          ← timestamp line
//   /                            ← path line
//   .......................       ← dots line
//   ~ 503.13ms                   ← duration line
//
// We collect those 4 lines and emit one clean line:
//   GET /  503ms
//
// It also emits startup lines we want to show once:
//   INFO  Server running on [http://127.0.0.1:8000].
//   Press Ctrl+C to stop the server

function makeServerParser(onReady) {
    let buf = '';
    let readyShown = false;

    function emitRequest(path, durStr) {
        const dur = durStr.replace(/^~\s*/, '').trim();
        let durColor = GREEN;
        const ms = parseFloat(dur);
        if (ms > 1000)      durColor = RED;
        else if (ms > 200)  durColor = ORANGE;
        const p = path.startsWith('/') ? path : `/${path}`;
        out(PRE_RED, `${WHITE}GET ${R}${D}${p.padEnd(30)}${R}  ${durColor}${dur}${R}`);
    }

    return function parse(rawChunk) {
        // Accumulate raw bytes, process complete lines only
        buf += rawChunk;

        // Split on newlines (handle \r\n and \n)
        const lines = buf.split(/\r?\n/);
        buf = lines.pop(); // last fragment — keep for next chunk

        for (const rawLine of lines) {
            const line = stripAnsi(rawLine).trim();
            if (!line) continue;

            // ── Ready ─────────────────────────────────────────────────────
            if (/Server running on/i.test(line)) {
                if (!readyShown) { readyShown = true; onReady(); }
                continue;
            }

            // ── Suppress noise ────────────────────────────────────────────
            if (/Press Ctrl/i.test(line)) continue;

            // ── Request log line ──────────────────────────────────────────
            // Format: "2026-09-22 11:13:03 /path ......... ~ 0.32ms"
            // Dots may contain ANSI codes per-dot (already stripped above)
            const req = line.match(
                /^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\s+(\/\S*)\s+[.\s]*(~\s*[\d.]+\s*ms)/i
            );
            if (req) {
                emitRequest(req[1], req[2]);
                continue;
            }

            // ── Anything else (errors, warnings, etc.) ────────────────────
            out(PRE_RED, `${D}${line}${R}`);
        }
    };
}

// ─── Queue worker output parser ───────────────────────────────────────────────
//
// queue:work outputs lines like:
//   2026-09-22 11:13:03 Processing: App\Jobs\SendEmail
//   2026-09-22 11:13:03 Processed:  App\Jobs\SendEmail (12.34ms)
//   2026-09-22 11:13:03 Failed:     App\Jobs\SendEmail (12.34ms)

function makeQueueParser() {
    return function parse(raw) {
        const line = stripAnsi(raw).trim();
        if (!line) return;

        // Strip leading timestamp
        const noTs = line.replace(/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\s+/, '');

        if (/^Processing:/i.test(noTs)) {
            const job = noTs.replace(/^Processing:\s*/i, '');
            out(PRE_ORANGE, `${D}job ${R}${job}`);
            return;
        }
        if (/^Processed:/i.test(noTs)) {
            const job = noTs.replace(/^Processed:\s*/i, '');
            out(PRE_ORANGE, `${GREEN}✓${R} ${job}`);
            return;
        }
        if (/^Failed:/i.test(noTs)) {
            const job = noTs.replace(/^Failed:\s*/i, '');
            out(PRE_ORANGE, `${RED}✗ failed${R} ${job}`);
            return;
        }

        // Suppress common noise
        if (/^\[.*\]$/.test(noTs)) return; // just a bracketed group

        // Show the rest dimmed
        if (line) out(PRE_ORANGE, `${D}${line}${R}`);
    };
}

// ─── startDev ─────────────────────────────────────────────────────────────────

const PORT = process.env.APP_PORT || process.env.PORT || '8000';

function startDev() {
    const startMs = Date.now();

    printLogo();
    out(PRE_WHITE, `${B}${WHITE}Laravel Development${R}`);
    out(PRE_WHITE, `${D}  Local:   ${R}${CYAN}http://localhost:${PORT}${R}`);
    process.stdout.write('\n');
    out(PRE_RED,    `${D}server   starting…${R}`);
    out(PRE_ORANGE, `${D}queue    starting…${R}`);
    process.stdout.write('\n');

    // ── spawn server ──────────────────────────────────────────────────────────
    // Run through bash with stdbuf to force line-buffering (Git Bash / Linux / macOS)
    const server = spawn(
        'bash',
        ['-c', `stdbuf -oL -eL php artisan serve --port=${PORT} 2>&1`],
        {
            cwd: projectRoot,
            stdio: ['ignore', 'pipe', 'pipe'],
            env: { ...process.env, PHP_CLI_SERVER_WORKERS: '1' },
        }
    );

    let readyEmitted = false;
    function emitReady() {
        if (readyEmitted) return;
        readyEmitted = true;
        clearTimeout(readyFallback);
        const elapsed = ((Date.now() - startMs) / 1000).toFixed(1);
        out(PRE_RED, `${GREEN}✓${R} Ready in ${elapsed}s`);
        process.stdout.write('\n');
    }

    const serverParse = makeServerParser(emitReady);

    // Fallback: show ready after 5s even if server output is buffered
    const readyFallback = setTimeout(emitReady, 5000);

    const feedServer = (data) => serverParse(data.toString());
    server.stdout.on('data', feedServer);
    server.stderr.on('data', feedServer);

    // ── spawn queue ───────────────────────────────────────────────────────────
    const queue = spawn('php', ['artisan', 'queue:work', '--tries=3'], {
        cwd: projectRoot,
        stdio: ['ignore', 'pipe', 'pipe'],
        env: { ...process.env },
    });

    const queueParse = makeQueueParser();
    const feedQueue = (data) => {
        data.toString().split('\n').forEach(l => queueParse(l));
    };
    queue.stdout.on('data', feedQueue);
    queue.stderr.on('data', feedQueue);

    // ── shutdown ──────────────────────────────────────────────────────────────
    let shuttingDown = false;

    function cleanExit() {
        if (shuttingDown) return;
        shuttingDown = true;
        process.stdout.write('\n');
        out(PRE_WHITE, `${D}stopping…${R}`);
        try { server.kill('SIGTERM'); } catch (_) {}
        try { queue.kill('SIGTERM'); }  catch (_) {}
        setTimeout(() => {
            out(PRE_WHITE, `${D}stopped${R}`);
            process.stdout.write('\n');
            process.exit(0);
        }, 600);
    }

    server.on('close', (code) => {
        if (!shuttingDown) {
            out(PRE_RED, `${RED}server exited (${code})${R}`);
            cleanExit();
        }
    });
    queue.on('close', (code) => {
        if (!shuttingDown) {
            out(PRE_ORANGE, `${ORANGE}queue exited (${code})${R}`);
        }
    });

    process.on('SIGINT',  cleanExit);
    process.on('SIGTERM', cleanExit);
}

// ─── forwardArtisan ───────────────────────────────────────────────────────────

function forwardArtisan(args) {
    const child = spawn('php', ['artisan', ...args], {
        cwd: projectRoot,
        stdio: 'inherit',
        env: { ...process.env },
    });
    child.on('close', (code) => process.exit(code ?? 0));
    child.on('error', (err) => {
        process.stderr.write(`[davingm] error: ${err.message}\n`);
        process.exit(1);
    });
}

// ─── Entry ────────────────────────────────────────────────────────────────────

if (!existsSync(resolve(projectRoot, 'artisan'))) {
    process.stderr.write(`[davingm] error: laravel project not found at ${projectRoot}\n`);
    process.exit(1);
}

const args = process.argv.slice(2);

if (args.length === 0) {
    printLogo();
    out(PRE_WHITE, `${B}${WHITE}davingm${R} ${D}Laravel CLI${R}`);
    process.stdout.write('\n');
    out(PRE_WHITE, `  ${CYAN}artisan dev${R}              start server + queue`);
    out(PRE_WHITE, `  ${CYAN}artisan <command>${R}        php artisan <command>`);
    process.stdout.write('\n');
    out(PRE_WHITE, `  ${D}artisan migrate${R}`);
    out(PRE_WHITE, `  ${D}artisan make:model User${R}`);
    out(PRE_WHITE, `  ${D}artisan route:list${R}`);
    process.stdout.write('\n');
    process.exit(0);
}

if (args[0] === 'dev') {
    startDev();
} else {
    forwardArtisan(args);
}
