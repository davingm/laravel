import { boot } from '@davingm/welcome';

boot();

function initializeHomeCounter(): void {
    const button = document.querySelector<HTMLButtonElement>('[data-home-counter]');
    const output = document.querySelector<HTMLOutputElement>('[data-home-counter-value]');

    if (!button || !output || button.dataset.counterInitialized === 'true') {
        return;
    }

    button.dataset.counterInitialized = 'true';
    let count = 0;

    button.addEventListener('click', () => {
        count += 1;
        output.value = String(count);
        output.textContent = String(count);
    });
}

initializeHomeCounter();
window.addEventListener('auto:navigated', initializeHomeCounter);
