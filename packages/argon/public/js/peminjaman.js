document.addEventListener('click', (event) => {
    const row = event.target.closest('tr[data-edit-url]');
    if (!row || event.target.closest('a,button,input,select,textarea,form,label')) return;
    window.location.assign(row.dataset.editUrl);
});

document.addEventListener('keydown', (event) => {
    const row = event.target.closest('tr[data-edit-url]');
    if (!row || event.target.closest('a,button,input,select,textarea,form,label')) return;
    if (!['Enter', ' '].includes(event.key)) return;
    event.preventDefault();
    window.location.assign(row.dataset.editUrl);
});

function updateRelativeTimes() {
    document.querySelectorAll('[data-relative-time]').forEach((element) => {
        const timestamp = Date.parse(element.dateTime);
        if (Number.isNaN(timestamp)) return;

        const minutes = Math.max(0, Math.floor((Date.now() - timestamp) / 60000));
        if (minutes < 1) {
            element.textContent = 'Baru saja';
        } else if (minutes < 60) {
            element.textContent = `${minutes} menit lalu`;
        } else if (minutes < 1440) {
            const hours = Math.floor(minutes / 60);
            element.textContent = `${hours} jam lalu`;
        } else {
            const days = Math.floor(minutes / 1440);
            element.textContent = `${days} hari lalu`;
        }
    });
}

updateRelativeTimes();
window.setInterval(updateRelativeTimes, 60000);
