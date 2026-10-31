import type { PagePayload } from './types';

export function readPagePayload(source: Document = document): PagePayload | null {
    const element = source.querySelector<HTMLScriptElement>('[data-page-payload]');

    return element?.textContent
        ? JSON.parse(element.textContent) as PagePayload
        : null;
}
