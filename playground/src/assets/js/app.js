const payloadElement = document.querySelector('[data-page-payload]');
const payload = payloadElement ? JSON.parse(payloadElement.textContent) : null;

window.__DAVINGM__ = {
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
			window.dispatchEvent(new CustomEvent('davingm:navigated', { detail: { url } }));
		}).catch(() => window.location.assign(url));
	},
};

document.addEventListener('click', (event) => {
	const link = event.target.closest('[data-navigate]');
	if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
	event.preventDefault();
	window.__DAVINGM__.navigate(link.dataset.navigate || link.href);
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
