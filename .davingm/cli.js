#!/usr/bin/env node

/**
 * davingm Laravel CLI
 * .davingm/cli.js
 *
 * Usage:
 *   artisan dev              → start Laravel server + queue worker
 *   artisan <command>        → forward to `php artisan <command>`
 */

import { spawn } from 'node:child_process';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { existsSync } from 'node:fs';

// ─── ANSI Colors ─────────────────────────────────────────────────────────────

const c = {
    reset:   '\x1b[0m',
    bold:    '\x1b[1m',
    dim:     '\x1b[2m',
    red:     '\x1b[31m',
    orange:  '\x1b[38;5;208m',
    white:   '\x1b[97m',
    gray:    '\x1b[90m',
    green:   '\x1b[32m',
    yellow:  '\x1b[33m',
    cyan:    '\x1b[36m',
};

// ─── Helpers ──────────────────────────────────────────────────────────────────

const prefix = `${c.bold}${c.white}[davingm]${c.reset}`;

function log(msg) {
    process.stdout.write(`${prefix} ${msg}\n`);
}

function logRed(msg) {
    process.stdout.write(`${prefix} ${c.red}${msg}${c.reset}\n`);
}

function logOrange(msg) {
    process.stdout.write(`${prefix} ${c.orange}${msg}${c.reset}\n`);
}

function logDim(msg) {
    process.stdout.write(`${prefix} ${c.dim}${msg}${c.reset}\n`);
}

function separator() {
    process.stdout.write(`${prefix} ${c.dim}${'─'.repeat(50)}${c.reset}\n`);
}

// ─── ASCII Logo ───────────────────────────────────────────────────────────────

function printLogo() {
    const logo = [
        '    __                               __',
        '   / /   ____ __________ __   _____  / /',
        '  / /   / __ `/ ___/ __ `/ | / / _ \\/ /',
        ' / /___/ /_/ / /  / /_/ /| |/ /  __/ /',
        '/_____/\\__,_/_/   \\__,_/ |___/\\___/_/',
    ];
    process.stdout.write('\n');
    for (const line of logo) {
        process.stdout.write(`  ${c.red}${c.bold}${line}${c.reset}\n`);
    }
    process.stdout.write('\n');
}

// ─── Project root detection ───────────────────────────────────────────────────

function findProjectRoot() {
    const __dir = dirname(fileURLToPath(import.meta.url));
    // .davingm/ is inside the project root, so go one level up
    const projectRoot = resolve(__dir, '..');
    return projectRoot;
}

// ─── PHP detection ───────────────────────────────────────────────────────────

function phpBin() {
    return 'php';
}

// ─── dev command ─────────────────────────────────────────────────────────────

const PORT = process.env.APP_PORT || process.env.PORT || '8000';

function startDev(projectRoot) {
    printLogo();
    separator();
    log(`${c.bold}${c.white}Laravel Development${c.reset}`);
    logDim(`Port : ${c.white}${PORT}`);
    logDim(`URL  : ${c.cyan}http://localhost:${PORT}`);
    separator();
    logRed('Laravel server  : starting...');
    logOrange('Queue worker    : starting...');
    separator();
    process.stdout.write('\n');

    // Spawn Laravel dev server
    const server = spawn(
        phpBin(),
        ['artisan', 'serve', `--port=${PORT}`, '--ansi'],
        {
            cwd: projectRoot,
            stdio: ['ignore', 'pipe', 'pipe'],
            env: { ...process.env },
        }
    );

    // Spawn queue worker
    const queue = spawn(
        phpBin(),
        ['artisan', 'queue:work', '--ansi', '--tries=3'],
        {
            cwd: projectRoot,
            stdio: ['ignore', 'pipe', 'pipe'],
            env: { ...process.env },
        }
    );

    // Pipe server output (red)
    server.stdout.on('data', (data) => {
        const lines = data.toString().replace(/\r/g, '').split('\n');
        for (const line of lines) {
            if (line.trim()) logRed(stripAnsi(line));
        }
    });
    server.stderr.on('data', (data) => {
        const lines = data.toString().replace(/\r/g, '').split('\n');
        for (const line of lines) {
            if (line.trim()) logRed(stripAnsi(line));
        }
    });

    // Pipe queue output (orange)
    queue.stdout.on('data', (data) => {
        const lines = data.toString().replace(/\r/g, '').split('\n');
        for (const line of lines) {
            if (line.trim()) logOrange(stripAnsi(line));
        }
    });
    queue.stderr.on('data', (data) => {
        const lines = data.toString().replace(/\r/g, '').split('\n');
        for (const line of lines) {
            if (line.trim()) logOrange(stripAnsi(line));
        }
    });

    // Handle process exits
    server.on('close', (code) => {
        separator();
        logRed(`Laravel server stopped (exit ${code}).`);
        if (!shuttingDown) cleanExit();
    });

    queue.on('close', (code) => {
        separator();
        logOrange(`Queue worker stopped (exit ${code}).`);
        if (!shuttingDown) cleanExit();
    });

    // Clean shutdown on Ctrl+C / SIGTERM
    let shuttingDown = false;

    function cleanExit(signal = '') {
        if (shuttingDown) return;
        shuttingDown = true;
        process.stdout.write('\n');
        separator();
        if (signal) log(`Received ${signal}. Stopping all processes...`);
        else        log('A process exited. Stopping all processes...');

        try { server.kill('SIGTERM'); } catch (_) {}
        try { queue.kill('SIGTERM'); }  catch (_) {}

        setTimeout(() => {
            separator();
            log('All processes stopped. Goodbye.');
            process.stdout.write('\n');
            process.exit(0);
        }, 800);
    }

    process.on('SIGINT',  () => cleanExit('SIGINT'));
    process.on('SIGTERM', () => cleanExit('SIGTERM'));
}

// ─── Strip ANSI helper ────────────────────────────────────────────────────────

// Keep it zero-dependency — simple regex
function stripAnsi(str) {
    return str.replace(/\x1b\[[0-9;]*m/g, '');
}

// ─── Forward artisan commands ─────────────────────────────────────────────────

function forwardArtisan(projectRoot, args) {
    const child = spawn(
        phpBin(),
        ['artisan', ...args],
        {
            cwd: projectRoot,
            stdio: 'inherit',
            env: { ...process.env },
        }
    );

    child.on('close', (code) => {
        process.exit(code ?? 0);
    });

    child.on('error', (err) => {
        process.stderr.write(`[davingm] Error: ${err.message}\n`);
        process.exit(1);
    });
}

// ─── Entry point ─────────────────────────────────────────────────────────────

const args = process.argv.slice(2);
const projectRoot = findProjectRoot();

// Validate project root has artisan
if (!existsSync(resolve(projectRoot, 'artisan'))) {
    process.stderr.write(
        `[davingm] Error: Could not locate Laravel project at: ${projectRoot}\n`
    );
    process.exit(1);
}

if (args.length === 0) {
    // No args — show help
    printLogo();
    separator();
    log(`${c.bold}${c.white}davingm Laravel CLI${c.reset}`);
    separator();
    logDim('Usage:');
    log(`  ${c.cyan}artisan dev${c.reset}          ${c.dim}→ start Laravel server + queue worker${c.reset}`);
    log(`  ${c.cyan}artisan <command>${c.reset}    ${c.dim}→ forward to \`php artisan <command>\`${c.reset}`);
    separator();
    logDim('Examples:');
    log(`  ${c.cyan}artisan migrate${c.reset}`);
    log(`  ${c.cyan}artisan make:model User${c.reset}`);
    log(`  ${c.cyan}artisan route:list${c.reset}`);
    log(`  ${c.cyan}artisan tinker${c.reset}`);
    separator();
    process.stdout.write('\n');
    process.exit(0);
}

if (args[0] === 'dev') {
    startDev(projectRoot);
} else {
    forwardArtisan(projectRoot, args);
}
