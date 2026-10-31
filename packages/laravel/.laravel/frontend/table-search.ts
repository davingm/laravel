export function registerTableSearch(): void {
    document.addEventListener('input', (event: Event) => {
        if (!(event.target instanceof HTMLInputElement) || !event.target.matches('[data-table-search]')) return;

        const search = event.target;
        const tableBody = search.closest('section')?.querySelector<HTMLTableSectionElement>('[data-table-body]');
        if (!tableBody) return;

        const query = search.value.trim().toLocaleLowerCase();
        const rows = [...tableBody.querySelectorAll<HTMLTableRowElement>('[data-table-row]')];
        let visibleRows = 0;

        for (const row of rows) {
            const matches = (row.textContent ?? '').toLocaleLowerCase().includes(query);
            row.hidden = !matches;
            if (matches) visibleRows++;
        }

        const noResults = tableBody.querySelector<HTMLTableRowElement>('[data-table-no-results]');
        if (noResults) noResults.hidden = query.length === 0 || visibleRows > 0 || rows.length === 0;
    });
}
