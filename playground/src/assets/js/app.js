const devTelemetryEnabled = import.meta.env.VITE_LARAVEL_CLI_TELEMETRY === '1';
const devTelemetryUrl = typeof window !== 'undefined'
	? `${window.location.origin}/__laravel/cli/telemetry`
	: '';

if (devTelemetryEnabled && typeof window !== 'undefined') {
	const report = (event) => {
		try {
			navigator.sendBeacon(devTelemetryUrl, new Blob([JSON.stringify(event)], { type: 'text/plain' }));
		} catch {
			// Telemetry is best-effort and must never block application requests.
		}
	};
	const requestPath = (input) => {
		try {
			return new URL(input, window.location.href).pathname;
		} catch {
			return String(input);
		}
	};
	const markPageReady = () => requestAnimationFrame(() => requestAnimationFrame(() => report({ type: 'page-ready' })));
	if (document.readyState === 'complete') queueMicrotask(markPageReady);
	else window.addEventListener('load', markPageReady, { once: true });

	const seenResources = new Set();
	const reportResource = (entry) => {
		if (['fetch', 'xmlhttprequest'].includes(entry.initiatorType) || entry.name.startsWith(devTelemetryUrl)) return;
		if (new URL(entry.name).origin === window.location.origin) return;
		const key = `${entry.name}:${entry.startTime}`;
		if (seenResources.has(key)) return;
		seenResources.add(key);
		const id = `resource-${key}`;
		report({ type: 'start', id, method: 'GET', path: requestPath(entry.name) });
		report({ type: 'complete', id, method: 'GET', path: requestPath(entry.name), milliseconds: entry.duration });
	};
	for (const entry of performance.getEntriesByType('resource')) reportResource(entry);
	try {
		new PerformanceObserver((list) => list.getEntries().forEach(reportResource)).observe({ type: 'resource', buffered: true });
	} catch {
		// PerformanceObserver is optional; fetch tracking still works.
	}
}

const payloadElement = document.querySelector('[data-page-payload]');
const payload = payloadElement ? JSON.parse(payloadElement.textContent) : null;

window.__AUTO_LARAVEL__ = {
	payload,
	navigate(url) {
		return fetch(url, {
			headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
		}).then((response) => {
			if (!response.ok) throw new Error(`Navigation failed: ${response.status}`);
			return response.text();
		}).then((html) => {
			const documentFromResponse = new DOMParser().parseFromString(html, 'text/html');
			const nextMain = documentFromResponse.querySelector('#page-view');
			const currentMain = document.querySelector('#page-view');

			if (!nextMain || !currentMain) {
				window.location.assign(url);
				return;
			}

			currentMain.replaceWith(nextMain);
			document.title = documentFromResponse.title;
			history.pushState({}, '', url);
			window.scrollTo({ top: 0, behavior: 'instant' });
			window.dispatchEvent(new CustomEvent('auto:navigated', { detail: { url } }));
		}).catch(() => window.location.assign(url));
	},
};

document.addEventListener('click', (event) => {
	const link = event.target.closest('[data-navigate]');
	if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
	event.preventDefault();
	window.__AUTO_LARAVEL__.navigate(link.dataset.navigate || link.href);
});

document.addEventListener('submit', (event) => {
	const message = event.target.dataset?.confirm;
	if (message && !window.confirm(message)) event.preventDefault();
});

document.addEventListener('input', (event) => {
	const search = event.target;
	if (!search.matches('[data-table-search]')) return;

	const tableBody = search.closest('section')?.querySelector('[data-table-body]');
	if (!tableBody) return;

	const query = search.value.trim().toLocaleLowerCase();
	const rows = [...tableBody.querySelectorAll('[data-table-row]')];
	let visibleRows = 0;

	for (const row of rows) {
		const matches = row.textContent.toLocaleLowerCase().includes(query);
		row.hidden = !matches;
		if (matches) visibleRows++;
	}

	const noResults = tableBody.querySelector('[data-table-no-results]');
	if (noResults) noResults.hidden = query.length === 0 || visibleRows > 0 || rows.length === 0;
});

document.querySelectorAll('[data-reveal]').forEach((element) => {
	element.style.setProperty('--reveal-delay', `${element.dataset.delay || 0}ms`);
});
