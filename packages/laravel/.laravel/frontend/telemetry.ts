interface TelemetryEnvironment {
    VITE_LARAVEL_CLI_TELEMETRY?: string;
}

interface TelemetryEvent {
    type: 'page-ready' | 'start' | 'complete';
    id?: string;
    method?: string;
    path?: string;
    milliseconds?: number;
}

function requestPath(input: string): string {
    try {
        return new URL(input, window.location.href).pathname;
    } catch {
        return input;
    }
}

export function registerDevelopmentTelemetry(): void {
    const environment = (import.meta as ImportMeta & { env?: TelemetryEnvironment }).env;
    if (environment?.VITE_LARAVEL_CLI_TELEMETRY !== '1') return;

    const telemetryUrl = `${window.location.origin}/__laravel/cli/telemetry`;
    const report = (event: TelemetryEvent): void => {
        try {
            navigator.sendBeacon(telemetryUrl, new Blob([JSON.stringify(event)], { type: 'text/plain' }));
        } catch {
            // Telemetry is best-effort and must never block application requests.
        }
    };
    const markPageReady = (): void => {
        requestAnimationFrame(() => requestAnimationFrame(() => report({ type: 'page-ready' })));
    };

    if (document.readyState === 'complete') queueMicrotask(markPageReady);
    else window.addEventListener('load', markPageReady, { once: true });

    const seenResources = new Set<string>();
    const reportResource = (entry: PerformanceResourceTiming): void => {
        if (['fetch', 'xmlhttprequest'].includes(entry.initiatorType) || entry.name.startsWith(telemetryUrl)) return;

        try {
            if (new URL(entry.name).origin === window.location.origin) return;
        } catch {
            return;
        }

        const key = `${entry.name}:${entry.startTime}`;
        if (seenResources.has(key)) return;
        seenResources.add(key);

        const id = `resource-${key}`;
        const path = requestPath(entry.name);
        report({ type: 'start', id, method: 'GET', path });
        report({ type: 'complete', id, method: 'GET', path, milliseconds: entry.duration });
    };

    performance.getEntriesByType('resource').forEach((entry) => reportResource(entry as PerformanceResourceTiming));

    try {
        new PerformanceObserver((list) => {
            list.getEntries().forEach((entry) => reportResource(entry as PerformanceResourceTiming));
        }).observe({ type: 'resource', buffered: true });
    } catch {
        // PerformanceObserver is optional; page-ready telemetry still works.
    }
}
