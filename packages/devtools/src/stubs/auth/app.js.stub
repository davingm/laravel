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

document.addEventListener('click', (event) => {
	const add = event.target.closest('[data-add-item]');
	if (add) {
		const container = add.closest('.crud-shell')?.querySelector('[data-items]');
		const template = add.closest('.crud-shell')?.querySelector('[data-item-template]');
		if (!container || !template) return;
		const index = container.querySelectorAll('[data-item-row]').length;
		const row = template.content.cloneNode(true);
		row.querySelectorAll('[data-name]').forEach((field) => field.name = `items[${index}][${field.dataset.name}]`);
		container.append(row);
	}
	const remove = event.target.closest('[data-remove-item]');
	if (remove) {
		const rows = remove.closest('[data-items]')?.querySelectorAll('[data-item-row]');
		if (rows?.length > 1) remove.closest('[data-item-row]').remove();
	}
});

document.addEventListener('change', (event) => {
	if (!event.target.matches('[data-items] select[name$="[barang_id]"]')) return;
	const price = event.target.selectedOptions[0]?.dataset.price;
	const input = event.target.closest('[data-item-row]')?.querySelector('input[name$="[unit_price]"]');
	if (input && (input.value === '' || input.dataset.autofilled === 'true')) {
		input.value = price || '0';
		input.dataset.autofilled = 'true';
	}
});

function syncTransferWarehouses(form, changedField = null) {
	const source = form.querySelector('[data-transfer-source]');
	const destination = form.querySelector('[data-transfer-destination]');
	if (!source || !destination) return;

	if (source.value && source.value === destination.value) {
		if (changedField === destination) destination.value = '';
		else destination.value = '';
	}

	for (const option of destination.options) option.disabled = Boolean(source.value && option.value === source.value);
	for (const option of source.options) option.disabled = Boolean(destination.value && option.value === destination.value);

	const error = form.querySelector('[data-warehouse-error]');
	if (error) error.hidden = !source.value || !destination.value || source.value !== destination.value;
}

async function refreshTransferStock(form, row) {
	const warehouseId = form.querySelector('[data-transfer-source]')?.value;
	const barang = row.querySelector('[data-transfer-barang]');
	const hint = row.querySelector('[data-stock-availability]');
	const quantity = row.querySelector('[data-transfer-quantity]');
	if (!barang || !hint || !quantity) return;

	const requestId = String(Number(row.dataset.stockRequest || 0) + 1);
	row.dataset.stockRequest = requestId;
	quantity.removeAttribute('max');
	if (!warehouseId || !barang.value) {
		hint.textContent = 'Pilih gudang asal dan barang untuk melihat stok.';
		return;
	}

	hint.textContent = 'Memuat stok...';
	const url = new URL(form.dataset.stockUrl, window.location.origin);
	url.searchParams.set('warehouse_id', warehouseId);
	url.searchParams.set('barang_id', barang.value);

	try {
		const response = await fetch(url, { headers: { Accept: 'application/json' } });
		if (!response.ok) throw new Error('Stock request failed');
		const stock = await response.json();
		if (row.dataset.stockRequest !== requestId) return;
		const amount = Number(stock.quantity || 0);
		quantity.max = String(amount);
		hint.textContent = amount > 0
			? `Stok tersedia di gudang asal: ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(amount)} ${stock.unit}.`
			: `Stok tersedia di gudang asal: 0 ${stock.unit}.`;
	} catch {
		if (row.dataset.stockRequest === requestId) hint.textContent = 'Stok tidak dapat dimuat. Coba pilih ulang barang.';
	}
}

document.querySelectorAll('[data-transfer-form]').forEach((form) => {
	syncTransferWarehouses(form);
	form.querySelectorAll('[data-item-row]').forEach((row) => refreshTransferStock(form, row));
});

document.addEventListener('change', (event) => {
	const form = event.target.closest('[data-transfer-form]');
	if (!form) return;

	if (event.target.matches('[data-transfer-source], [data-transfer-destination]')) {
		syncTransferWarehouses(form, event.target);
		form.querySelectorAll('[data-item-row]').forEach((row) => refreshTransferStock(form, row));
	}

	if (event.target.matches('[data-transfer-barang]')) {
		const row = event.target.closest('[data-item-row]');
		if (row) refreshTransferStock(form, row);
	}
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
