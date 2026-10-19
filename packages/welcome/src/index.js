import './welcomepage.css';
import { createRenderer } from './renderer.js';

/**
 * Boot the WebGL mountain background for any <x-welcomepage> on the page.
 * Called once on DOMContentLoaded (or immediately if the DOM is already ready).
 */
export function boot() {
    const canvas = document.querySelector('[data-welcomepage-canvas]');
    const slot   = document.querySelector('[data-welcomepage-slot]');

    if (!(canvas instanceof HTMLCanvasElement) || !slot) return;
    if (!window.WebGL2RenderingContext) { canvas.remove(); return; }

    try {
        const renderer = createRenderer(canvas, slot);
        renderer.onFirstFrame = () => { canvas.style.opacity = '1'; };
        window.addEventListener('pagehide', renderer.dispose, { once: true });
    } catch {
        canvas.remove();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
