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

document.querySelectorAll('[data-reveal]').forEach((element) => {
	element.style.setProperty('--reveal-delay', `${element.dataset.delay || 0}ms`);
});
