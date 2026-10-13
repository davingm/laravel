import process from 'node:process';

const ESC = '\x1b[';

/** Keeps a small block of CLI output pinned to the bottom of the terminal. */
export function createTerminalPanel(enabled) {
    let open = false;
    let topRow = 0;
    let lines = [];

    function terminalRows() {
        return process.stdout.rows || 24;
    }

    function clearRows(first, count) {
        if (!first || count <= 0) return '';
        let output = '';
        for (let row = first; row < first + count; row++) {
            output += `${ESC}${Math.min(row, terminalRows())};1H${ESC}2K`;
        }
        return output;
    }

    function render(nextLines) {
        if (!enabled) return;

        const rows = terminalRows();
        const nextTopRow = Math.max(1, rows - nextLines.length + 1);

        if (!open) {
            const padding = Math.max(0, rows - nextLines.length - 1);
            if (padding) process.stdout.write('\r\n'.repeat(padding));
            process.stdout.write('\x1b[s\x1b[?25l');
            open = true;
        }

        let output = '';
        if (topRow && topRow !== nextTopRow) {
            output += clearRows(topRow, lines.length);
            lines = [];
        }

        const count = Math.max(lines.length, nextLines.length);
        for (let index = 0; index < count; index++) {
            const next = nextLines[index] ?? '';
            if (next === lines[index]) continue;
            output += `${ESC}${nextTopRow + index};1H${ESC}2K${next}`;
        }

        if (output) process.stdout.write(output);
        lines = [...nextLines];
        topRow = nextTopRow;
    }

    function onResize() {
        if (open) render(lines);
    }

    if (enabled) process.stdout.on('resize', onResize);

    return {
        render,
        clear() {
            if (!enabled) return;
            process.stdout.write('\x1b[2J');
            lines = [];
            topRow = 0;
        },
        close() {
            if (!enabled) return;
            process.stdout.off('resize', onResize);
            const output = clearRows(topRow, lines.length);
            process.stdout.write(`${output}\x1b[u\x1b[?25h`);
            open = false;
            lines = [];
            topRow = 0;
        },
    };
}
