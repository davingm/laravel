function applyRevealDelay(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-reveal]').forEach((element) => {
        element.style.setProperty('--reveal-delay', `${element.dataset.delay || 0}ms`);
    });
}

export function registerReveal(): void {
    applyRevealDelay();
    window.addEventListener('auto:navigated', () => applyRevealDelay());
}
