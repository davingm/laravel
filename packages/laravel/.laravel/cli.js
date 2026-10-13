#!/usr/bin/env node

/**
 * Laravel CLI
 * .laravel/cli.js
 */

import { spawn } from 'node:child_process';
import { emitKeypressEvents } from 'node:readline';
import { resolve, dirname } from 'node:path';
import { existsSync, mkdirSync, readFileSync, unlinkSync, writeFileSync } from 'node:fs';
import { networkInterfaces } from 'node:os';
import { createTerminalPanel } from './cli/terminal-panel.js';
import { createRequestTelemetry } from './cli/request-telemetry.js';

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

const PRE_RED    = `${B}${RED}[laravel]${R}`;
const PRE_ORANGE = `${B}${ORANGE}[laravel]${R}`;
const PRE_WHITE  = `${B}${WHITE}[laravel]${R}`;

const stripAnsi = (s) => s.replace(/\x1b\[[0-9;]*m/g, '').replace(/\r/g, '');

// ─── Printers ─────────────────────────────────────────────────────────────────

let activeDevUI = null;
const out = (pre, msg) => {
    const line = stripAnsi(`${pre} ${msg}`);
    if (activeDevUI) activeDevUI.log(line);
    else process.stdout.write(`${pre} ${msg}\n`);
};

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

function findProjectRoot(startDirectory) {
    let directory = resolve(startDirectory);

    while (true) {
        if (existsSync(resolve(directory, 'artisan')) && existsSync(resolve(directory, '.laravel', 'cli.js'))) {
            return directory;
        }

        const parent = dirname(directory);
        if (parent === directory) return null;
        directory = parent;
    }
}

const projectRoot = findProjectRoot(process.cwd());

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

function makeServerParser(onListening, onRequest, log) {
    let buf = '';
    let listeningShown = false;

    function emitRequest(path, durStr, source = 'server') {
        const dur = durStr.replace(/^~\s*/, '').trim();
        const ms = parseFloat(dur);
        const p = path.startsWith('/') ? path : `/${path}`;
        onRequest({ method: 'GET', path: p, duration: dur, milliseconds: ms, source });
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
                if (!listeningShown) { listeningShown = true; onListening(); }
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
                if (req[1].startsWith('/__laravel/cli/telemetry')) continue;
                if (/\.[a-z\d]{2,8}(?:\?|$)/i.test(req[1])) emitRequest(req[1], req[2], 'php-static');
                continue;
            }

            // ── Anything else (errors, warnings, etc.) ────────────────────
            log(line);
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

function createDevUI(url, startedAt, enabled) {
    const state = { status: 'STARTING', detail: 'preparing app', progress: 0, ceiling: 0, rate: 0, readyElapsed: null, mode: 'overview', requests: [], pendingRequests: new Map(), logs: [], routes: [], infoRows: [], routeRefreshRunning: false, serverListening: false, servicesReady: false, firstPageReady: false, selection: 0, offset: 0, follow: true, errorsOnly: false, selectedRequest: null, selectedRoute: null, phpVersion: process.env.PHP_VERSION || 'detecting…' };
    let keyHandler;
    let routeTimer;
    const panel = createTerminalPanel(enabled);

    function readJson(path) {
        try { return JSON.parse(readFileSync(path, 'utf8')); } catch { return null; }
    }

    function packageVersion(name) {
        return readJson(resolve(projectRoot, 'node_modules', ...name.split('/'), 'package.json'))?.version || 'not installed';
    }

    const lock = readJson(resolve(projectRoot, 'composer.lock')) || {};
    const laravelPackage = [...(lock.packages || []), ...(lock['packages-dev'] || [])].find(pkg => pkg.name === 'laravel/framework');

    function medianMilliseconds(requests = state.requests) {
        const values = requests.map(request => Number(request.milliseconds)).filter(Number.isFinite).sort((a, b) => a - b);
        if (!values.length) return null;
        const middle = Math.floor(values.length / 2);
        return values.length % 2 ? values[middle] : (values[middle - 1] + values[middle]) / 2;
    }

    function logTime(timestamp) {
        return new Date(timestamp || Date.now()).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' });
    }

    function trafficTime(timestamp) {
        return new Date(timestamp || Date.now()).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit' });
    }

    function width() { return Math.max(40, (process.stdout.columns || 80) - 4); }
    function truncate(line) {
        const max = Math.max(24, width() - 4);
        return line.length > max ? `${line.slice(0, max - 1)}…` : line;
    }

    function isBundlerRequest(request) {
        return /(?:\/@vite\/|\/@id\/|\/node_modules\/|\/(?:src|resources)\/|\?t=|hot-update)/.test(request.path);
    }

    function networkRows() {
        return state.requests.filter(request => !isBundlerRequest(request));
    }

    function logRows() {
        return state.errorsOnly ? state.logs.filter(entry => entry.level === 'error') : state.logs;
    }

    function rowsForCurrentView() {
        if (state.mode === 'logs') return logRows();
        if (state.mode === 'network') return networkRows();
        if (state.mode === 'routes') return state.routes;
        if (state.mode === 'info') return state.infoRows;
        return [];
    }

    function visibleCount() {
        return Math.max(1, (process.stdout.rows || 24) - (state.mode === 'routes' ? 4 : 5));
    }

    function routeRefresh() {
        if (state.routeRefreshRunning) return;
        state.routeRefreshRunning = true;
        const child = spawn('php', ['artisan', 'route:list', '--json'], { cwd: projectRoot, stdio: ['ignore', 'pipe', 'pipe'] });
        let output = '';
        let errorOutput = '';
        child.stdout.on('data', chunk => { output += chunk.toString(); });
        child.stderr.on('data', chunk => { errorOutput += chunk.toString(); });
        child.on('error', error => { state.routeError = error.message; render(); });
        child.on('close', code => {
            state.routeRefreshRunning = false;
            if (code !== 0) state.routeError = errorOutput.trim() || `route:list exited (${code})`;
            else {
                try {
                    state.routes = JSON.parse(output).filter(route => route.uri !== '__laravel/cli/telemetry').map(route => ({
                        method: route.method || 'ANY', uri: route.uri || '/', name: route.name || '', action: route.action || '', middleware: Array.isArray(route.middleware) ? route.middleware.join(', ') : '',
                    }));
                    state.routeError = '';
                } catch (error) { state.routeError = `could not read route:list JSON: ${error.message}`; }
            }
            if (state.mode === 'routes') {
                render();
                clearTimeout(routeTimer);
                routeTimer = setTimeout(routeRefresh, 2000);
                routeTimer.unref?.();
            }
        });
    }

    function render() {
        if (!enabled) return;
        const cyan = '\x1b[36m', green = '\x1b[32m', gray = '\x1b[90m', bold = '\x1b[1m', reset = '\x1b[0m';
        const median = medianMilliseconds();
        const latest = state.requests.at(-1);
        const badge = {
            STARTING: '\x1b[97;43;1m STARTING \x1b[0m',
            WARMUP: '\x1b[97;48;5;208;1m WARMUP \x1b[0m',
            READY: '\x1b[97;42;1m READY \x1b[0m',
            'DOWN SERVER': '\x1b[97;41;1m DOWN SERVER \x1b[0m',
        }[state.status] || `\x1b[97;43;1m ${state.status} \x1b[0m`;
        let lines;
        if (state.mode === 'overview') {
            lines = [`  ${green}···${reset}   ${bold}Laravel${reset} ${gray}development${reset}`, '', `  ${gray}Local${reset}   ${cyan}${url}${reset}`, ''];
            if (state.status === 'READY' || state.status === 'WARMUP' || state.status === 'DOWN SERVER') {
                const timing = median !== null ? `${median.toFixed(1)}ms median` : state.readyElapsed ? `ready in ${state.readyElapsed}s` : 'waiting for server';
                lines.push(`  ${green}✓ ${state.requests.length} requests${reset}   ${gray}${timing}${reset}`);
                lines.push('');
                const requestLabel = latest
                    ? `${latest.method} ${latest.path}${latest.status ? ` · ${latest.status}` : ''}${latest.duration ? ` · ${latest.duration}` : ''}`
                    : 'waiting for requests';
                const activity = state.status === 'WARMUP'
                    ? state.detail
                    : state.status === 'DOWN SERVER'
                        ? state.detail
                        : requestLabel;
                lines.push(`  ${badge}  ${activity}`);
            } else {
                const elapsed = ((Date.now() - startedAt) / 1000).toFixed(1);
                const percent = Math.min(100, Math.floor(state.progress));
                const width = 26;
                const filled = Math.round(width * percent / 100);
                const bar = `${green}${'━'.repeat(filled)}${gray}${'━'.repeat(width - filled)}${reset}`;
                lines.push(`  ${bar}  ${percent}%  ${gray}· ${elapsed}s${reset}`);
                lines.push('');
                lines.push(`  ${badge}  ${state.detail}`);
            }
            lines.push(`  ${gray}r${reset} restart · ${gray}o${reset} open · ${gray}i${reset} info · ${gray}l${reset} logs · ${gray}n${reset} network · ${gray}p${reset} routes · ${gray}?${reset} help · ${gray}q${reset} quit`);
        } else if (state.mode === 'logs') {
            const entries = logRows();
            const count = visibleCount();
            const maxOffset = Math.max(0, entries.length - count);
            if (state.follow) state.offset = maxOffset;
            state.offset = Math.max(0, Math.min(state.offset, maxOffset));
            state.selection = state.follow ? Math.max(0, entries.length - 1) : Math.max(0, Math.min(state.selection, entries.length - 1));
            const visible = entries.slice(state.offset, state.offset + count);
            lines = [
                `  ${gray}↑/↓ select · Home/End top/live · PgUp/PgDn scroll · e errors · q close${reset}`,
                `  ${bold}logs${reset} · follow ${state.follow ? `${green}live${reset}` : `${gray}paused${reset}`} · cli+build+runtime · ${state.errorsOnly ? 'errors only' : 'all levels'}`,
                `  ${gray}${'─'.repeat(width())}${reset}`,
                ...(visible.length ? visible.map((entry, index) => {
                    const color = entry.level === 'error' ? '\x1b[31m' : entry.level === 'warn' ? '\x1b[38;5;208m' : reset;
                    return `${state.offset + index === state.selection ? `${green}▎${reset}` : ' '} ${gray}${logTime(entry.timestamp)}${reset} ${color}${truncate(entry.message)}${reset}`;
                }) : [`  ${gray}${state.errorsOnly ? 'No errors captured.' : 'No logs captured yet.'}${reset}`]),
                `  ${gray}↑/↓ select · Home/End top/live · PgUp/PgDn scroll · e errors · q close${reset}`,
            ];
        } else if (state.mode === 'info') {
            const uptime = (Date.now() - startedAt) / 1000;
            const networkAddress = Object.values(networkInterfaces()).flat().find(item => item && item.family === 'IPv4' && !item.internal)?.address;
            const infoRows = [
                `${bold}versions${reset}`,
                `  Laravel  ${green}${laravelPackage?.version || 'not installed'}${reset}`,
                `  PHP      ${green}${state.phpVersion}${reset}`,
                `  Vite     ${green}${packageVersion('vite')}${reset}`,
                `  Node     ${green}${process.version}${reset}`,
                '',
                `${bold}urls${reset}`,
                `  local    ${cyan}${url}${reset}`,
                ...(networkAddress ? [`  network  ${cyan}http://${networkAddress}:${url.match(/:(\d+)/)?.[1] || '8000'}/${reset}`] : []),
                '',
                `${bold}session${reset}`,
                `  uptime   ${green}${uptime >= 60 ? `${Math.floor(uptime / 60)}m ${(uptime % 60).toFixed(0)}s` : `${uptime.toFixed(1)}s`}${reset}`,
                `  requests ${state.requests.length}`,
                `  median   ${median !== null ? `${median.toFixed(1)}ms` : '—'}`,
                `  directory ${truncate(projectRoot)}`,
            ];
            state.infoRows = infoRows;
            const selectedInfoRows = infoRows.map((row, index) => `${index === state.selection ? `${green}▎${reset}` : ' '} ${row}`);
            lines = [`  ${gray}↑/↓ select · y copy · Y copy for an issue · q close${reset}`, `  ${bold}info${reset}`, `  ${gray}${'─'.repeat(width())}${reset}`, ...selectedInfoRows, '', `  ${gray}↑/↓ select · y copy · Y copy for an issue · q close${reset}`];
        } else {
            const count = visibleCount();
            if (state.mode === 'network') {
                const requests = networkRows();
                state.selection = Math.max(0, Math.min(state.selection, requests.length - 1));
                const mid = medianMilliseconds(requests);
                const hidden = state.requests.filter(isBundlerRequest).length;
                const maxOffset = Math.max(0, requests.length - count);
                state.offset = Math.max(0, Math.min(state.offset, maxOffset));
                const visible = requests.slice(state.offset, state.offset + count);
                lines = [`  ${bold}traffic${reset} · all (${requests.length}) · ${state.requests.length} total · median ${mid === null ? '—' : `${mid.toFixed(0)}ms`} · ${hidden} bundler hidden`, `  ${gray}${'─'.repeat(width())}${reset}`,
                    ...(visible.length ? visible.map((request, index) => {
                        const selected = state.offset + index === state.selection;
                        const statusColor = String(request.status).startsWith('2') ? green : String(request.status).startsWith('4') || String(request.status).startsWith('5') ? '\x1b[31m' : cyan;
                        const duration = request.duration || `${Number(request.milliseconds || 0).toFixed(1)}ms`;
                        return `${selected ? `${green}▎${reset}` : ' '} ${gray}${trafficTime(request.timestamp)}${reset} ${String(request.method).padEnd(6)} ${statusColor}${String(request.status || '—').padEnd(5)}${reset} ${String(duration).padStart(7)}  ${truncate(request.path)}`;
                    }) : [`  ${gray}Waiting for requests…${reset}`]),
                    `  ${gray}↑/↓ select · enter trace · Home/End top/live · q close${reset}`];
            } else if (state.mode === 'routes') {
                state.selection = Math.max(0, Math.min(state.selection, state.routes.length - 1));
                const maxOffset = Math.max(0, state.routes.length - count);
                state.offset = Math.max(0, Math.min(state.offset, maxOffset));
                const visible = state.routes.slice(state.offset, state.offset + count);
                lines = [`  ${bold}routes${reset} · ${state.routes.length} route(s) · refreshes every 2s`, `  ${gray}${'─'.repeat(width())}${reset}`,
                    ...(state.routeError ? [`  \x1b[31m${truncate(state.routeError)}${reset}`] : visible.map((route, index) => `${state.offset + index === state.selection ? `${green}▎${reset}` : ' '} ${String(route.method).padEnd(12)} ${truncate(route.uri)}${route.name ? `  ${gray}${route.name}${reset}` : ''}`)),
                    `  ${gray}↑/↓ select · enter details · Home/End top/bottom · q close${reset}`];
            } else if (state.mode === 'trace') {
                const request = state.selectedRequest;
                lines = [`  ${gray}↑/↓ select · q close${reset}`, `  ${bold}request trace${reset}`, `  ${gray}${'─'.repeat(width())}${reset}`,
                    `  method     ${request?.method || '—'}`, `  status     ${request?.status || '—'}`, `  duration   ${request?.duration || '—'}`, `  path       ${request?.path || '—'}`, `  source     ${request?.source || 'frontend/server'}`,
                    '', `  ${gray}q / esc return to network${reset}`];
            } else if (state.mode === 'route-detail') {
                const route = state.selectedRoute;
                lines = [`  ${gray}↑/↓ select · q close${reset}`, `  ${bold}route details${reset}`, `  ${gray}${'─'.repeat(width())}${reset}`,
                    `  method     ${route?.method || '—'}`, `  uri        ${route?.uri || '—'}`, `  name       ${route?.name || '—'}`, `  action     ${route?.action || '—'}`, `  middleware ${route?.middleware || '—'}`, '', `  ${gray}q / esc return to routes${reset}`];
            } else if (state.mode === 'help') {
                lines = [`  ${bold}keyboard shortcuts${reset}`, `  ${gray}${'─'.repeat(width())}${reset}`,
                    `  ${green}global${reset}`, '  ctrl-c / ctrl-d       quit development servers', '  ctrl-l                redraw this panel', '  ctrl-r                restart the web server', '',
                    `  ${green}main panel${reset}`, '  r                     restart the web server', '  shift-r               clear Laravel caches and restart', '  o                     open in browser', '  y                     copy server URL', '  c                     clear console (keep history)', '  x                     delete log and request history', '  e                     open logs filtered to errors', '  i / u                 show versions, URLs, and session info', '  l                     browse log history', '  n                     browse network requests', '  p                     browse artisan routes', '',
                    `  ${gray}↑/↓ select · PgUp/PgDn scroll · esc/q close${reset}`];
            } else {
                lines = [`  ${bold}${state.mode}${reset}`, `  ${gray}${'─'.repeat(width())}${reset}`, `  ${gray}esc / q close${reset}`];
            }
        }

        const maxLines = Math.max(1, (process.stdout.rows || 24) - 1);
        if (lines.length > maxLines) lines = lines.slice(0, maxLines - 1).concat(lines.at(-1));
        panel.render(lines);
    }

    function refreshPhpVersion() {
        const child = spawn('php', ['-r', 'echo PHP_VERSION;'], { cwd: projectRoot, stdio: ['ignore', 'pipe', 'ignore'] });
        let version = '';
        child.stdout.on('data', chunk => { version += chunk.toString(); });
        child.on('close', code => {
            if (code === 0 && version.trim()) state.phpVersion = version.trim();
            if (state.mode === 'info') render();
        });
    }

    function copyText(text) {
        const [command, args] = process.platform === 'win32' ? ['clip.exe', []]
            : process.platform === 'darwin' ? ['pbcopy', []]
                : ['xclip', ['-selection', 'clipboard']];
        const child = spawn(command, args, { stdio: ['pipe', 'ignore', 'ignore'] });
        child.on('error', () => {});
        child.stdin.end(text);
    }

    function setMode(mode) {
        state.mode = mode;
        state.selection = 0;
        state.offset = 0;
        state.follow = mode === 'logs';
        if (mode === 'logs') {
            state.selection = Math.max(0, logRows().length - 1);
            state.offset = Math.max(0, logRows().length - visibleCount());
        }
        if (mode === 'info') refreshPhpVersion();
        if (mode === 'routes') {
            routeRefresh();
        } else {
            clearTimeout(routeTimer);
            routeTimer = null;
        }
        render();
    }

    function moveSelection(delta) {
        const entries = rowsForCurrentView();
        if (!entries.length) return;
        const page = visibleCount();
        if (state.mode === 'logs' && state.follow) {
            state.selection = Math.max(0, entries.length - 1);
            state.follow = false;
        }
        state.selection = Math.max(0, Math.min(entries.length - 1, state.selection + delta));
        if (state.selection < state.offset) state.offset = state.selection;
        if (state.selection >= state.offset + page) state.offset = state.selection - page + 1;
        render();
    }

    function scrollPage(direction) {
        const entries = rowsForCurrentView();
        const page = visibleCount();
        const maxOffset = Math.max(0, entries.length - page);
        state.follow = false;
        state.offset = Math.max(0, Math.min(maxOffset, state.offset + direction * page));
        state.selection = Math.max(state.offset, Math.min(entries.length - 1, state.offset + page - 1));
        render();
    }

    const ui = {
        interactive: enabled,
        log(line) {
            const message = stripAnsi(line);
            const level = /\b(error|exception|fatal|failed|critical)\b/i.test(message) ? 'error' : /\b(warn|warning)\b/i.test(message) ? 'warn' : 'info';
            state.logs.push({ message, level, timestamp: Date.now() });
            if (state.logs.length > 1000) state.logs.shift();
            if (enabled) render();
            else process.stdout.write(`${line}\n`);
        },
        setStatus(status, detail = '') {
            state.status = status;
            state.detail = detail;
            if (status === 'LISTENING') {
                state.serverListening = true;
                state.status = 'STARTING';
                state.detail = detail || 'waiting for frontend server';
                render();
            } else if (status === 'READY') {
                state.servicesReady = true;
                state.progress = 100;
                state.ceiling = 100;
                state.rate = 0;
                state.readyElapsed = ((Date.now() - startedAt) / 1000).toFixed(1);
                if (state.pendingRequests.size) {
                    const pending = [...state.pendingRequests.values()].at(-1);
                    state.status = 'WARMUP';
                    state.detail = `rendering ${pending.method} ${pending.path}`;
                } else {
                    state.status = 'READY';
                    state.detail = detail || 'watching for requests';
                }
                if (!enabled && state.status === 'READY') this.log(`✓ Ready in ${state.readyElapsed}s`);
                else render();
            } else {
                if (status === 'DOWN SERVER') state.rate = 0;
                render();
            }
        },
        beginRequest(request) {
            let recentServerRequest = -1;
            for (let index = state.requests.length - 1; index >= 0; index--) {
                const item = state.requests[index];
                if (item.source === 'server' && item.path === request.path && Date.now() - item.timestamp < 5000) {
                    recentServerRequest = index;
                    break;
                }
            }
            if (recentServerRequest >= 0) state.requests.splice(recentServerRequest, 1);
            const pending = { ...request, timestamp: Date.now() };
            pending.timeout = setTimeout(() => {
                if (!state.pendingRequests.has(request.id)) return;
                state.pendingRequests.delete(request.id);
                this.log(`request timed out: ${request.method} ${request.path}`);
                this.updateRequestStatus();
            }, 30_000);
            pending.timeout.unref?.();
            state.pendingRequests.set(request.id, pending);
            state.status = 'WARMUP';
            state.detail = `rendering ${request.method} ${request.path}`;
            if (enabled) render();
            else process.stdout.write(`[WARMUP] ${state.detail}\n`);
        },
        finishRequest(request) {
            const pending = state.pendingRequests.get(request.id);
            if (pending?.timeout) clearTimeout(pending.timeout);
            state.pendingRequests.delete(request.id);
            state.requests.push({ ...request, timestamp: request.timestamp || Date.now(), source: request.source || 'frontend' });
            if (state.requests.length > 500) state.requests.shift();
            this.updateRequestStatus();
            if (enabled) render();
            else process.stdout.write(`[frontend] ${request.method} ${request.path} ${request.status} ${request.duration}\n`);
        },
        setProgress(progress, detail, ceiling = progress, rate = 0) {
            if (progress < 100) {
                state.firstPageReady = false;
                state.servicesReady = false;
                state.serverListening = false;
                for (const pending of state.pendingRequests.values()) clearTimeout(pending.timeout);
                state.pendingRequests.clear();
            }
            state.status = 'STARTING';
            state.detail = detail;
            state.progress = progress;
            state.ceiling = Math.max(progress, ceiling);
            state.rate = rate;
            if (!enabled) this.log(`[${Math.floor(state.progress)}%] ${detail}`);
            else render();
        },
        addRequest(request) {
            if ([...state.pendingRequests.values()].some(pending => pending.path === request.path)) return;
            state.requests.push({ ...request, timestamp: Date.now(), source: request.source || 'server' });
            if (state.requests.length > 500) state.requests.shift();
            if (enabled) render();
            else this.log(`${request.method} ${request.path}  ${request.duration}`);
        },
        markPageReady() {
            state.firstPageReady = true;
            for (const pending of state.pendingRequests.values()) clearTimeout(pending.timeout);
            state.pendingRequests.clear();
            this.setStatus('READY', 'watching for requests');
        },
        updateRequestStatus() {
            if (state.pendingRequests.size) {
                const pending = [...state.pendingRequests.values()].at(-1);
                state.status = 'WARMUP';
                state.detail = `rendering ${pending.method} ${pending.path}`;
            } else if (state.servicesReady || state.firstPageReady) {
                state.status = 'READY';
                state.detail = 'watching for requests';
            } else if (state.serverListening) {
                state.status = 'STARTING';
                state.detail = 'waiting for first page to render';
            }
            render();
        },
        setMode,
        handleViewKey(key) {
            if (key.name === 'up') { moveSelection(-1); return true; }
            if (key.name === 'down') { moveSelection(1); return true; }
            if (key.name === 'pageup') { scrollPage(-1); return true; }
            if (key.name === 'pagedown') { scrollPage(1); return true; }
            if (key.name === 'home') {
                state.follow = false; state.selection = 0; state.offset = 0; render(); return true;
            }
            if (key.name === 'end') {
                const entries = rowsForCurrentView();
                state.selection = Math.max(0, entries.length - 1);
                state.offset = Math.max(0, entries.length - visibleCount());
                state.follow = state.mode === 'logs'; render(); return true;
            }
            if (state.mode === 'logs' && key.sequence === 'e') {
                state.errorsOnly = !state.errorsOnly; state.selection = Math.max(0, logRows().length - 1); state.follow = true; render(); return true;
            }
            if (state.mode === 'network' && key.name === 'return') {
                state.selectedRequest = networkRows()[state.selection] || null;
                state.mode = 'trace'; render(); return true;
            }
            if (state.mode === 'routes' && key.name === 'return') {
                state.selectedRoute = state.routes[state.selection] || null;
                clearTimeout(routeTimer);
                routeTimer = null;
                state.mode = 'route-detail'; render(); return true;
            }
            if (state.mode === 'info' && (key.sequence === 'y' || key.sequence === 'Y')) {
                const issue = key.sequence === 'Y' || key.shift;
                const summary = `Laravel ${laravelPackage?.version || 'unknown'} | PHP ${state.phpVersion} | Vite ${packageVersion('vite')} | Node ${process.version}\nLocal: ${url}\nUptime: ${((Date.now() - startedAt) / 1000).toFixed(1)}s | Requests: ${state.requests.length} | Median: ${medianMilliseconds() ?? '—'}ms\nDirectory: ${projectRoot}`;
                const selected = stripAnsi(state.infoRows[state.selection] || summary);
                copyText(issue ? `Development issue\n\n${summary}\n\nLogs:\n${state.logs.slice(-30).map(entry => entry.message).join('\n')}` : selected);
                return true;
            }
            return false;
        },
        getMode() { return state.mode; },
        copyText,
        setErrorsOnly(value) { state.errorsOnly = value; state.follow = true; state.selection = Math.max(0, logRows().length - 1); render(); },
        redraw() { panel.clear(); render(); },
        clearConsole() { state.mode = 'overview'; panel.clear(); render(); },
        clearHistory() { state.logs = []; state.requests = []; state.pendingRequests.clear(); state.selection = 0; state.offset = 0; render(); },
        attachKeys(handler) {
            if (!enabled) return;
            emitKeypressEvents(process.stdin);
            try { process.stdin.setRawMode(true); } catch { return; }
            process.stdin.resume();
            keyHandler = (_text, key) => handler(key || {});
            process.stdin.on('keypress', keyHandler);
        },
        close() {
            clearInterval(progressTimer);
            clearInterval(routeTimer);
            if (keyHandler) process.stdin.off('keypress', keyHandler);
            if (enabled) {
                try { process.stdin.setRawMode(false); } catch {}
                panel.close();
                process.stdout.write('\n');
            }
        },
        render,
    };

    const progressTimer = enabled ? setInterval(() => {
        if (state.status === 'READY' || state.progress >= state.ceiling || state.rate <= 0) return;
        state.progress = Math.min(state.ceiling, state.progress + state.rate / 10);
        render();
    }, 100) : null;
    progressTimer?.unref?.();

    return ui;
}

function startDev(extraArgs = []) {
    const args = extraArgs.filter(arg => arg !== '--no-tui');
    const portArg = args.find(arg => arg.startsWith('--port='));
    const portIndex = args.indexOf('--port');
    const port = portArg ? portArg.slice('--port='.length) : portIndex >= 0 ? (args[portIndex + 1] || PORT) : PORT;
    const url = `http://localhost:${port}/`;
    const startedAt = Date.now();
    const useTui = process.stdin.isTTY && process.stdout.isTTY && !process.env.CI && !process.env.NO_TUI && !extraArgs.includes('--no-tui');
    const ui = createDevUI(url, startedAt, useTui);
    activeDevUI = ui;
    ui.render();
    if (!useTui) ui.log('Laravel Development');

    const previewFlag = resolve(projectRoot, '.laravel', '.preview');
    if (existsSync(previewFlag)) {
        try { unlinkSync(previewFlag); } catch {}
    }

    let shuttingDown = false;
    let restarting = false;
    let restartAfterClose = false;
    let clearCacheOnRestart = false;
    let preparationFinished = false;
    let prepare;
    let server;
    let queue;
    let vite = null;
    let artisanListening = false;
    let viteRequired = false;
    let viteListening = false;
    let viteProblem = '';
    const telemetry = createRequestTelemetry(ui);
    let telemetryUrl = '';
    const telemetryFile = resolve(projectRoot, '.laravel', 'cache', 'cli-telemetry-url');

    function clearViteHotFile() {
        if (!vite) return;
        const hotFile = resolve(projectRoot, 'public', 'hot');
        if (existsSync(hotFile)) {
            try { unlinkSync(hotFile); } catch (error) { ui.log(`could not remove stale Vite hot file: ${error.message}`); }
        }
    }

    function markDevReady() {
        if (shuttingDown || !artisanListening || (viteRequired && !viteListening)) return;
        restarting = false;
        const elapsed = ((Date.now() - startedAt) / 1000).toFixed(1);
        ui.setStatus('READY', viteProblem || `ready in ${elapsed}s`);
    }

    function cleanExit() {
        if (shuttingDown) return;
        shuttingDown = true;
        ui.close();
        activeDevUI = null;
        process.stdout.write('Stopping development servers…\n');
        try { prepare?.kill('SIGTERM'); } catch {}
        try { server?.kill('SIGTERM'); } catch {}
        try { queue?.kill('SIGTERM'); } catch {}
        try { vite?.kill('SIGTERM'); } catch {}
        clearViteHotFile();
        try { if (existsSync(telemetryFile)) unlinkSync(telemetryFile); } catch {}
        telemetry.close();
        setTimeout(() => process.exit(0), 500);
    }

    function openBrowser() {
        const command = process.platform === 'win32' ? ['cmd.exe', ['/c', 'start', '', url]]
            : process.platform === 'darwin' ? ['open', [url]] : ['xdg-open', [url]];
        const child = spawn(command[0], command[1], { detached: true, stdio: 'ignore' });
        child.on('error', error => ui.log(`could not open browser: ${error.message}`));
        child.unref();
    }

    function clearCachesThen(callback) {
        ui.log('clearing Laravel caches…');
        const child = spawn('php', ['artisan', 'optimize:clear'], { cwd: projectRoot, stdio: ['ignore', 'pipe', 'pipe'] });
        let output = '';
        let completed = false;
        const finish = () => {
            if (completed) return;
            completed = true;
            callback();
        };
        child.stdout.on('data', chunk => { output += chunk.toString(); });
        child.stderr.on('data', chunk => { output += chunk.toString(); });
        child.on('close', code => {
            if (output.trim()) output.trim().split(/\r?\n/).forEach(line => ui.log(line));
            if (code !== 0) ui.log(`optimize:clear failed (${code}); restarting anyway`);
            finish();
        });
        child.on('error', error => { ui.log(`could not clear caches: ${error.message}`); finish(); });
    }

    function restartServer(clearCache = false) {
        if (restarting) return;
        restarting = true;
        clearCacheOnRestart = clearCache;
        ui.setProgress(65, clearCache ? 'clearing cache and restarting server' : 'restarting web server', 95, 3);
        if (server) {
            restartAfterClose = true;
            server.kill('SIGTERM');
            return;
        }
        restarting = false;
        if (clearCache) clearCachesThen(launchServer);
        else launchServer();
    }

    function launchServer() {
        artisanListening = false;
        const child = spawn('bash', ['-c', `stdbuf -oL -eL php artisan serve --host=0.0.0.0 --port=${port} 2>&1`], {
            cwd: projectRoot,
            stdio: ['ignore', 'pipe', 'pipe'],
            env: { ...process.env, PHP_CLI_SERVER_WORKERS: '1', LARAVEL_CLI_TELEMETRY_URL: telemetryUrl },
        });
        server = child;
        const parser = makeServerParser(() => {
            artisanListening = true;
            ui.setStatus('LISTENING', viteRequired && !viteListening ? 'waiting for Vite to start' : 'Laravel server listening');
            markDevReady();
        }, request => ui.addRequest(request), line => ui.log(line));
        child.stdout.on('data', data => parser(data.toString()));
        child.stderr.on('data', data => parser(data.toString()));
        child.on('error', error => {
            ui.log(`server error: ${error.message}`);
            ui.setStatus('DOWN SERVER', 'failed to start · press l for logs, r to restart');
        });
        child.on('close', code => {
            if (shuttingDown) return;
            if (server === child) server = null;
            if (restartAfterClose) {
                restartAfterClose = false;
                const clearCache = clearCacheOnRestart;
                clearCacheOnRestart = false;
                if (clearCache) clearCachesThen(launchServer);
                else launchServer();
                return;
            }
            ui.log(`server exited (${code})`);
            ui.setStatus('DOWN SERVER', `server exited (${code}) · press l for logs, r to restart`);
        });
    }

    function captureChildLogs(stream, source = '') {
        let buffer = '';
        stream.on('data', chunk => {
            const lines = `${buffer}${chunk.toString()}`.split(/\r?\n/);
            buffer = lines.pop() || '';
            for (const line of lines) if (line.trim()) ui.log(source ? `[${source}] ${line.trim()}` : line.trim());
        });
        stream.on('end', () => { if (buffer.trim()) ui.log(source ? `[${source}] ${buffer.trim()}` : buffer.trim()); });
    }

    async function startServices() {
        if (shuttingDown) return;
        ui.setProgress(45, 'starting background services', 60, 2);
        try {
            telemetryUrl = await telemetry.listen();
            mkdirSync(dirname(telemetryFile), { recursive: true });
            writeFileSync(telemetryFile, telemetryUrl, { mode: 0o600 });
            telemetry.onPageReady(() => ui.markPageReady());
        } catch (error) {
            ui.log(`frontend request tracking unavailable: ${error.message}`);
        }

        queue = spawn('php', ['artisan', 'queue:work', '--tries=3'], {
            cwd: projectRoot,
            stdio: ['ignore', 'pipe', 'pipe'],
            env: { ...process.env },
        });
        const queueParser = makeQueueParser();
        const feedQueue = data => data.toString().split('\n').forEach(line => queueParser(line));
        queue.stdout.on('data', feedQueue);
        queue.stderr.on('data', feedQueue);
        queue.on('close', code => { if (!shuttingDown) ui.log(`queue worker exited (${code})`); });

        const viteEntry = resolve(projectRoot, 'node_modules/vite/bin/vite.js');
        if (!existsSync(viteEntry)) {
            viteRequired = false;
            ui.log('Frontend dependencies missing. Run npm ci in the project root.');
        } else {
            viteRequired = true;
            const viteOutput = [];
            let viteBuffer = '';
            const captureViteOutput = data => {
                viteOutput.push(data.toString());
                if (viteOutput.join('').length > 12000) viteOutput.splice(0, viteOutput.length - 1);
                const lines = `${viteBuffer}${data.toString()}`.split(/\r?\n/);
                viteBuffer = lines.pop() || '';
                for (const rawLine of lines) {
                    const line = stripAnsi(rawLine).trim();
                    if (!line) continue;
                    ui.log(`[vite] ${line}`);
                    if (/\bready in\s+[\d.]+\s*ms\b/i.test(line)) {
                        viteListening = true;
                        markDevReady();
                    }
                }
            };
            vite = spawn(process.execPath, [viteEntry], { cwd: projectRoot, stdio: ['ignore', 'pipe', 'pipe'], env: { ...process.env, VITE_LARAVEL_CLI_TELEMETRY: telemetryUrl ? '1' : '0' } });
            vite.stdout.on('data', captureViteOutput);
            vite.stderr.on('data', captureViteOutput);
            vite.on('error', error => {
                ui.log(`frontend server error: ${error.message}`);
                clearViteHotFile();
                viteRequired = false;
                viteProblem = 'Vite failed to start · check logs';
                markDevReady();
            });
            vite.on('close', code => {
                clearViteHotFile();
                if (!shuttingDown && code !== 0) {
                    ui.log(`frontend dev server exited (${code})`);
                    const output = viteOutput.join('').trim();
                    if (output) ui.log(output);
                }
                if (!shuttingDown && !viteListening) {
                    viteRequired = false;
                    viteProblem = 'Vite did not start · check logs';
                    markDevReady();
                }
            });
        }

        ui.setProgress(65, 'starting web server', 95, 3);
        launchServer();
    }

    function finishPreparation(code, error = null) {
        if (preparationFinished || shuttingDown) return;
        preparationFinished = true;
        if (error || code !== 0) ui.log('frontend manifest skipped');
        ui.setProgress(35, 'starting background services', 45, 2);
        startServices().catch(error => ui.log(`could not start development services: ${error.message}`));
    }

    ui.attachKeys(key => {
        if (key.ctrl && (key.name === 'c' || key.name === 'd')) return cleanExit();
        if (key.ctrl && key.name === 'l') return ui.redraw();
        if (key.ctrl && key.name === 'r') return restartServer(false);
        if (key.name === 'q' || key.name === 'escape') {
            if (ui.getMode() === 'overview') return key.name === 'q' ? cleanExit() : undefined;
            if (ui.getMode() === 'trace') return ui.setMode('network');
            if (ui.getMode() === 'route-detail') return ui.setMode('routes');
            return ui.setMode('overview');
        }
        if (ui.getMode() !== 'overview') {
            if (ui.handleViewKey(key)) return;
            return;
        }
        if (key.name === 'r' && key.shift) return restartServer(true);
        if (key.name === 'r') return restartServer(false);
        if (key.name === 'o') return openBrowser();
        if (key.sequence === 'y') return ui.copyText(url);
        if (key.name === 'c') { ui.clearConsole(); return; }
        if (key.name === 'x') { ui.clearHistory(); return; }
        if (key.name === 'e') { ui.setErrorsOnly(true); return ui.setMode('logs'); }
        if (key.name === 'i' || key.name === 'u') return ui.setMode('info');
        if (key.name === 'l') { ui.setErrorsOnly(false); return ui.setMode('logs'); }
        if (key.name === 'n') return ui.setMode('network');
        if (key.name === 'p') return ui.setMode('routes');
        if (key.sequence === '?') return ui.setMode('help');
    });

    process.on('SIGINT', cleanExit);
    process.on('SIGTERM', cleanExit);
    let frontendPreparationStarted = false;
    function prepareFrontend() {
        if (frontendPreparationStarted || shuttingDown) return;
        frontendPreparationStarted = true;
        ui.setProgress(12, 'preparing app', 35, 3);
        prepare = spawn('php', ['artisan', 'frontend:generate'], { cwd: projectRoot, stdio: ['ignore', 'pipe', 'pipe'] });
        captureChildLogs(prepare.stdout, 'build');
        captureChildLogs(prepare.stderr, 'build');
        prepare.on('error', error => finishPreparation(1, error));
        prepare.on('close', code => finishPreparation(code));
    }

    ui.setProgress(0, 'refreshing development routes', 12, 3);
    prepare = spawn('php', ['artisan', 'route:clear'], { cwd: projectRoot, stdio: ['ignore', 'pipe', 'pipe'] });
    captureChildLogs(prepare.stdout, 'runtime');
    captureChildLogs(prepare.stderr, 'runtime');
    prepare.on('error', error => {
        ui.log(`could not refresh route cache: ${error.message}`);
        prepareFrontend();
    });
    prepare.on('close', code => {
        if (code !== 0) ui.log(`route:clear exited (${code}); continuing startup`);
        prepareFrontend();
    });
}
function startPreview(extraArgs) {
    forwardArtisan(['preview', ...extraArgs]);
}

// ─── startBuild ───────────────────────────────────────────────────────────────

function startBuild(extraArgs) {
    // Forward to php artisan build — the PHP command handles all steps
    // including npm via proc_open. We just forward and inherit stdio.
    printLogo();
    out(PRE_WHITE, `${B}${WHITE}artisan build${R}`);
    process.stdout.write('\n');
    forwardArtisan(['build', ...extraArgs]);
}

// ─── forwardArtisan ───────────────────────────────────────────────────────────

function forwardArtisan(args) {
    const child = spawn('php', ['artisan', ...args], {
        cwd: projectRoot,
        stdio: 'inherit',
        env: { ...process.env },
    });
    child.on('close', (code) => {
        const previewFlag = resolve(projectRoot, '.laravel', '.preview');
        if (existsSync(previewFlag)) {
            try { unlinkSync(previewFlag); } catch (_) {}
        }
        process.exit(code ?? 0);
    });
    child.on('error', (err) => {
        process.stderr.write(`[laravel] error: ${err.message}\n`);
        process.exit(1);
    });
}

// ─── Entry ────────────────────────────────────────────────────────────────────

if (projectRoot === null) {
    process.stderr.write('[laravel] error: no Laravel project found in this directory or its parents.\n');
    process.exit(1);
}

const args = process.argv.slice(2);

if (args.length === 0) {
    printLogo();
    out(PRE_WHITE, `${B}${WHITE}Laravel${R} ${D}CLI${R}`);
    process.stdout.write('\n');
    out(PRE_WHITE, `  ${CYAN}artisan dev${R}              start server + queue`);
    out(PRE_WHITE, `  ${CYAN}artisan dev --no-tui${R}     plain output without the terminal panel`);
    out(PRE_WHITE, `  ${CYAN}artisan build${R}            build for production`);
    out(PRE_WHITE, `  ${CYAN}artisan preview${R}          serve in production preview mode`);
    out(PRE_WHITE, `  ${CYAN}artisan <command>${R}        php artisan <command>`);
    process.stdout.write('\n');
    out(PRE_WHITE, `  ${D}artisan migrate${R}`);
    out(PRE_WHITE, `  ${D}artisan make:model User${R}`);
    out(PRE_WHITE, `  ${D}artisan route:list${R}`);
    process.stdout.write('\n');
    process.exit(0);
}

if (args[0] === 'dev') {
    startDev(args.slice(1));
} else if (args[0] === 'build') {
    startBuild(args.slice(1));
} else if (args[0] === 'preview') {
    startPreview(args.slice(1));
} else {
    forwardArtisan(args);
}
