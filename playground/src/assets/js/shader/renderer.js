import fragmentSource from './mountains.frag.glsl?raw';
import vertexSource from './mountains.vert.glsl?raw';

const COLS = 520;
const ROWS = 190;
const VERTICES = COLS * ROWS * 6;
const MAX_PIXEL_RATIO = 1.6;
const MAX_RENDER_WIDTH = 2400;
const FRAME_INTERVAL_MS = 1000 / 30;
const POINTER_FOLLOW_SECONDS = 0.22;
const POINTER_HOLD_SECONDS = 0.35;
const WAVE_RATE = 0.55;
const MAX_WAVES = 8;
const SOFTWARE_RENDERERS = /swiftshader|llvmpipe|softpipe|software|basic render/i;

function follow(current, target, deltaTime, seconds) {
	const alpha = 1 - Math.exp(-Math.min(Math.max(deltaTime, 0), 0.05) / Math.max(seconds, 0.001));
	return [
		current[0] + (target[0] - current[0]) * alpha,
		current[1] + (target[1] - current[1]) * alpha,
	];
}

function compile(gl, type, source) {
	const shader = gl.createShader(type);
	if (!shader) throw new Error('Could not create a WebGL shader.');
	gl.shaderSource(shader, source);
	gl.compileShader(shader);
	if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
		const message = gl.getShaderInfoLog(shader);
		gl.deleteShader(shader);
		throw new Error(`Mountain shader failed to compile: ${message}`);
	}
	return shader;
}

export function createRenderer(canvas, slot) {
	const gl = canvas.getContext('webgl2', {
		alpha: false,
		antialias: false,
		depth: false,
		stencil: false,
		powerPreference: 'low-power',
	});
	if (!gl) throw new Error('WebGL2 is unavailable.');

	const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
	const rendererName = String(
		(debugInfo && gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL))
		|| gl.getParameter(gl.RENDERER)
		|| '',
	);
	if (SOFTWARE_RENDERERS.test(rendererName)) {
		throw new Error(`WebGL is using a software renderer (${rendererName}).`);
	}

	const vertex = compile(gl, gl.VERTEX_SHADER, vertexSource);
	const fragment = compile(gl, gl.FRAGMENT_SHADER, fragmentSource);
	const program = gl.createProgram();
	if (!program) throw new Error('Could not create the mountain shader program.');
	gl.attachShader(program, vertex);
	gl.attachShader(program, fragment);
	gl.linkProgram(program);
	gl.deleteShader(vertex);
	gl.deleteShader(fragment);
	if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
		const message = gl.getProgramInfoLog(program);
		gl.deleteProgram(program);
		throw new Error(`Mountain shader program failed to link: ${message}`);
	}

	gl.useProgram(program);
	// WebGL2 requires a VAO even though every particle is derived from gl_VertexID.
	gl.bindVertexArray(gl.createVertexArray());
	gl.disable(gl.DEPTH_TEST);
	gl.enable(gl.BLEND);
	gl.blendFuncSeparate(gl.ONE, gl.ONE, gl.ONE, gl.ONE);

	const uniform = (name) => gl.getUniformLocation(program, name);
	const uniforms = {
		logoCentre: uniform('uLogoCentre'),
		logoScale: uniform('uLogoScale'),
		resolution: uniform('uResolution'),
		pointer: uniform('uPointer'),
		waves: uniform('uWave[0]'),
		hoverWave: uniform('uHoverWave'),
		misc: uniform('uMisc'),
	};
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
	const waves = new Float32Array(MAX_WAVES).fill(-1);
	let width = 1;
	let height = 1;
	let logoCentre = [0.5, 0.5];
	let logoScale = [0.2, 0.2];
	let pointer;
	let smoothedPointer = [0.5, 0.5];
	let pointerHold = 0;
	let hoverWave = -1;
	let startTime = 0;
	let lastRender = -Infinity;
	let frameId = 0;
	let disposed = false;
	let painted = false;
	let observer;
	let api;

	const schedule = () => {
		if (!disposed && !document.hidden && !frameId) {
			frameId = requestAnimationFrame(draw);
		}
	};
	const redraw = () => {
		lastRender = -Infinity;
		schedule();
	};
	const measure = () => {
		const bounds = canvas.getBoundingClientRect();
		if (bounds.width <= 0 || bounds.height <= 0) return;
		const ratio = Math.min(window.devicePixelRatio || 1, MAX_PIXEL_RATIO, MAX_RENDER_WIDTH / bounds.width);
		const nextWidth = Math.max(1, Math.floor(bounds.width * ratio));
		const nextHeight = Math.max(1, Math.floor(bounds.height * ratio));
		if (nextWidth !== width || nextHeight !== height) {
			width = nextWidth;
			height = nextHeight;
			canvas.width = width;
			canvas.height = height;
			gl.viewport(0, 0, width, height);
		}
		const slotBounds = slot.getBoundingClientRect();
		logoCentre = [
			(slotBounds.left + slotBounds.width / 2 - bounds.left) / Math.max(1, bounds.width),
			(slotBounds.top + slotBounds.height / 2 - bounds.top) / Math.max(1, bounds.height),
		];
		logoScale = [
			Math.max(0.001, slotBounds.width / Math.max(1, bounds.width)),
			Math.max(0.001, slotBounds.height / Math.max(1, bounds.height)),
		];
		redraw();
	};
	const handlePointerMove = (event) => {
		if (event.pointerType === 'touch') return;
		const bounds = canvas.getBoundingClientRect();
		pointer = [
			Math.min(1, Math.max(0, (event.clientX - bounds.left) / Math.max(1, bounds.width))),
			Math.min(1, Math.max(0, (event.clientY - bounds.top) / Math.max(1, bounds.height))),
		];
	};
	const handlePointerLeave = () => { pointer = undefined; };
	const handleClick = () => {
		let index = waves.findIndex((phase) => phase < 0);
		if (index < 0) index = waves.indexOf(Math.max(...waves));
		waves[index] = 0;
		redraw();
	};
	const handlePointerEnter = (event) => {
		if (event.pointerType === 'touch') return;
		hoverWave = 0;
		redraw();
	};
	const handleContextLost = (event) => {
		event.preventDefault();
		dispose();
		canvas.remove();
	};

	function draw(now) {
		frameId = 0;
		if (disposed || document.hidden) return;
		if (now - lastRender < FRAME_INTERVAL_MS) {
			schedule();
			return;
		}
		lastRender = now;
		const time = (now - startTime) / 1000;
		const deltaTime = Math.min(Math.max(time - lastFrameTime, 0), 0.05);
		lastFrameTime = time;
		smoothedPointer = follow(smoothedPointer, pointer || smoothedPointer, deltaTime, POINTER_FOLLOW_SECONDS);
		pointerHold += ((pointer ? 1 : 0) - pointerHold) * (1 - Math.exp(-deltaTime / POINTER_HOLD_SECONDS));
		for (let index = 0; index < waves.length; index += 1) {
			if (waves[index] < 0) continue;
			waves[index] += deltaTime * WAVE_RATE;
			if (waves[index] >= 1) waves[index] = -1;
		}
		if (hoverWave >= 0) {
			hoverWave += deltaTime * WAVE_RATE;
			if (hoverWave >= 1) hoverWave = -1;
		}

		gl.useProgram(program);
		gl.uniform2f(uniforms.logoCentre, logoCentre[0], logoCentre[1]);
		gl.uniform2f(uniforms.logoScale, logoScale[0], logoScale[1]);
		gl.uniform2f(uniforms.resolution, width, height);
		gl.uniform2f(uniforms.pointer, smoothedPointer[0], smoothedPointer[1]);
		gl.uniform1fv(uniforms.waves, waves);
		gl.uniform1f(uniforms.hoverWave, hoverWave);
		gl.uniform2f(uniforms.misc, time, pointerHold);
		gl.clearColor(0, 0, 0, 1);
		gl.clear(gl.COLOR_BUFFER_BIT);
		gl.drawArrays(gl.TRIANGLES, 0, VERTICES);

		if (!painted) {
			painted = true;
			api.onFirstFrame?.();
		}
		if (!reducedMotion.matches) schedule();
	}

	let lastFrameTime = 0;
	const handleVisibility = () => {
		if (document.hidden) {
			if (frameId) cancelAnimationFrame(frameId);
			frameId = 0;
		} else {
			redraw();
		}
	};
	const handleMotionChange = () => {
		if (frameId) cancelAnimationFrame(frameId);
		frameId = 0;
		redraw();
	};
	function dispose() {
		if (disposed) return;
		disposed = true;
		if (frameId) cancelAnimationFrame(frameId);
		observer?.disconnect();
		window.removeEventListener('pointermove', handlePointerMove);
		document.documentElement.removeEventListener('pointerleave', handlePointerLeave);
		document.removeEventListener('visibilitychange', handleVisibility);
		slot.removeEventListener('click', handleClick);
		slot.removeEventListener('pointerenter', handlePointerEnter);
		reducedMotion.removeEventListener('change', handleMotionChange);
		canvas.removeEventListener('webglcontextlost', handleContextLost);
		gl.deleteProgram(program);
	}

	api = { dispose, onFirstFrame: undefined };
	startTime = performance.now();
	observer = typeof ResizeObserver === 'undefined' ? undefined : new ResizeObserver(measure);
	observer?.observe(canvas);
	observer?.observe(slot);
	window.addEventListener('pointermove', handlePointerMove, { passive: true });
	document.documentElement.addEventListener('pointerleave', handlePointerLeave);
	document.addEventListener('visibilitychange', handleVisibility);
	slot.addEventListener('click', handleClick);
	slot.addEventListener('pointerenter', handlePointerEnter);
	reducedMotion.addEventListener('change', handleMotionChange);
	canvas.addEventListener('webglcontextlost', handleContextLost);
	measure();
	return api;
}
