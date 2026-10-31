export function registerFormConfirmations(): void {
    document.addEventListener('submit', (event: SubmitEvent) => {
        if (!(event.target instanceof HTMLFormElement)) return;
        const message = event.target.dataset.confirm;

        if (message && !window.confirm(message)) event.preventDefault();
    });
}
