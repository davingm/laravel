export type SeoMetaInput = {
    title?: string;
    description?: string;
    ogTitle?: string;
    ogDescription?: string;
    ogImage?: string;
    ogUrl?: string;
    ogType?: string;
    twitterTitle?: string;
    twitterDescription?: string;
    twitterImage?: string;
    twitterCard?: string;
    articleAuthor?: string;
};

type MetaAttribute = 'name' | 'property';

function setMeta(attribute: MetaAttribute, key: string, value: string | undefined): void {
    if (value === undefined) return;

    let element = document.head.querySelector<HTMLMetaElement>(`meta[${attribute}="${key}"]`);

    if (!element) {
        element = document.createElement('meta');
        element.setAttribute(attribute, key);
        document.head.append(element);
    }

    element.content = value;
}

/** Update the document metadata for the current page. SSR values are compiled by the framework. */
export function useSeoMeta(meta: SeoMetaInput): void {
    if (typeof document === 'undefined') return;

    if (meta.title !== undefined) document.title = meta.title;
    setMeta('name', 'description', meta.description);
    setMeta('property', 'og:title', meta.ogTitle ?? meta.title);
    setMeta('property', 'og:description', meta.ogDescription ?? meta.description);
    setMeta('property', 'og:image', meta.ogImage);
    setMeta('property', 'og:url', meta.ogUrl);
    setMeta('property', 'og:type', meta.ogType);
    setMeta('name', 'twitter:title', meta.twitterTitle ?? meta.ogTitle ?? meta.title);
    setMeta('name', 'twitter:description', meta.twitterDescription ?? meta.ogDescription ?? meta.description);
    setMeta('name', 'twitter:image', meta.twitterImage ?? meta.ogImage);
    setMeta('name', 'twitter:card', meta.twitterCard);
    setMeta('property', 'article:author', meta.articleAuthor);
}
