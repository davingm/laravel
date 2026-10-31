import { readPagePayload } from './payload';
import type { LaravelFrontendRuntime } from './types';

const seoMetaSelector = 'meta[name="description"], meta[name^="twitter:"], meta[property^="og:"], meta[property="article:author"]';

export async function navigateTo(url: string): Promise<void> {
    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        });
        if (!response.ok) throw new Error(`Navigation failed: ${response.status}`);

        const html = await response.text();
        const nextDocument = new DOMParser().parseFromString(html, 'text/html');
        const nextMain = nextDocument.querySelector<HTMLElement>('#page-view');
        const currentMain = document.querySelector<HTMLElement>('#page-view');

        if (!nextMain || !currentMain) {
            window.location.assign(url);
            return;
        }

        currentMain.replaceWith(nextMain);
        document.title = nextDocument.title;
        document.head.querySelectorAll(seoMetaSelector).forEach((element) => element.remove());
        nextDocument.head.querySelectorAll(seoMetaSelector).forEach((element) => {
            document.head.append(element.cloneNode(true));
        });

        window.__AUTO_LARAVEL__.payload = readPagePayload(nextDocument);
        history.pushState({}, '', url);
        window.scrollTo({ top: 0, behavior: 'instant' });
        window.dispatchEvent(new CustomEvent('auto:navigated', { detail: { url } }));
    } catch {
        window.location.assign(url);
    }
}

export function registerNavigation(runtime: LaravelFrontendRuntime): void {
    document.addEventListener('click', (event: MouseEvent) => {
        if (!(event.target instanceof Element)) return;
        const link = event.target.closest<HTMLAnchorElement>('[data-navigate]');
        if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        void runtime.navigate(link.dataset.navigate || link.href);
    });
}
