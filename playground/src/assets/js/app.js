import { createRenderer } from './shader/renderer.js';

const startMountainField = () => {
	const canvas = document.querySelector('[data-particle-field]');
	const lockup = document.querySelector('.starter-center');
	if (!(canvas instanceof HTMLCanvasElement) || !lockup || !window.WebGL2RenderingContext) return;

	try {
		const renderer = createRenderer(canvas, lockup);
		renderer.onFirstFrame = () => { canvas.style.opacity = '1'; };
		window.addEventListener('pagehide', renderer.dispose, { once: true });
	} catch (error) {
		console.warn('The WebGL2 mountain background could not start:', error);
		canvas.remove();
	}
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', startMountainField, { once: true });
} else {
	startMountainField();
}
