import { createServer } from 'node:http';

export function createRequestTelemetry(ui) {
    let pageReadyHandler = null;
    const server = createServer((request, response) => {
        response.setHeader('Access-Control-Allow-Origin', '*');
        response.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
        response.setHeader('Access-Control-Allow-Headers', 'Content-Type');

        if (request.method === 'OPTIONS') {
            response.writeHead(204).end();
            return;
        }

        if (request.method !== 'POST' || request.url !== '/requests') {
            response.writeHead(404).end();
            return;
        }

        let body = '';
        request.setEncoding('utf8');
        request.on('data', chunk => {
            body += chunk;
            if (body.length > 16_384) request.destroy();
        });
        request.on('end', () => {
            try {
                const event = JSON.parse(body);
                if (event.type === 'page-ready') {
                    pageReadyHandler?.();
                    response.writeHead(204).end();
                    return;
                }
                if (!event.id || !event.method || !event.path) throw new Error('invalid request event');
                if (event.type === 'start') {
                    ui.beginRequest({ id: String(event.id), method: String(event.method).toUpperCase(), path: String(event.path) });
                } else if (event.type === 'server-request') {
                    const milliseconds = Number(event.milliseconds) || 0;
                    ui.addRequest({
                        id: String(event.id),
                        method: String(event.method).toUpperCase(),
                        path: String(event.path),
                        status: event.status == null ? '' : String(event.status),
                        milliseconds,
                        duration: `${milliseconds.toFixed(1)}ms`,
                        source: 'laravel',
                    });
                } else if (event.type === 'complete') {
                    const milliseconds = Number(event.milliseconds) || 0;
                    ui.finishRequest({
                        id: String(event.id),
                        method: String(event.method).toUpperCase(),
                        path: String(event.path),
                        status: event.status == null ? '' : String(event.status),
                        milliseconds,
                        duration: `${milliseconds.toFixed(1)}ms`,
                    });
                }
                response.writeHead(204).end();
            } catch {
                response.writeHead(400).end();
            }
        });
    });

    return {
        onPageReady(handler) {
            pageReadyHandler = handler;
        },
        listen() {
            return new Promise((resolve, reject) => {
                server.once('error', reject);
                server.listen(0, '127.0.0.1', () => {
                    server.off('error', reject);
                    resolve(`http://127.0.0.1:${server.address().port}/requests`);
                });
            });
        },
        close() {
            if (server.listening) server.close();
        },
    };
}
