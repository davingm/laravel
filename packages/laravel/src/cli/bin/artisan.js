#!/usr/bin/env node

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

function findProjectRoot(startDirectory) {
    let directory = resolve(startDirectory);

    while (true) {
        if (
            existsSync(resolve(directory, 'composer.json'))
            && existsSync(resolve(directory, 'bootstrap', 'app.php'))
            && existsSync(resolve(directory, '.laravel', 'cli.js'))
            && existsSync(resolve(directory, '.laravel', 'artisan.php'))
        ) {
            return directory;
        }

        const parent = dirname(directory);
        if (parent === directory) return null;
        directory = parent;
    }
}

const projectRoot = findProjectRoot(process.cwd());

if (!projectRoot) {
    console.error('artisan: no davingm/laravel project found in this directory or its parents.');
    process.exit(1);
}

const runtimeCli = resolve(projectRoot, '.laravel', 'cli.js');
const child = spawn(process.execPath, [runtimeCli, ...process.argv.slice(2)], {
    cwd: process.cwd(),
    env: process.env,
    stdio: 'inherit',
});

child.on('error', (error) => {
    console.error(`artisan: unable to start the project CLI: ${error.message}`);
    process.exitCode = 1;
});

child.on('exit', (code, signal) => {
    process.exitCode = code ?? (signal === 'SIGINT' ? 130 : 1);
});
