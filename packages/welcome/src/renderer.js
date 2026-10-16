import vertexSource from './shader/vert.glsl.js';
import fragmentSource from './shader/frag.glsl.js';

const COLS = 704;
const ROWS = 260;
const VERTICES = COLS * ROWS * 6;
const MAX_PIXEL_RATIO = 1.6;
const MAX_RENDER_WIDTH = 2400;
const FRAME_INTERVAL_MS = 1000 / 30;
const POINTER_FOLLOW_SECONDS = 0.22;
const POINTER_HOLD_SECONDS = 0.35;
const WAVE_RATE = 0.55;
const MAX_WAVES = 8;
const SOFTWARE_RENDERERS = /swiftshader|llvmpipe|softpipe|software|basic render/i;

function follow(current, target, dt, seconds) {
    const alpha = 1 - Math.exp(-Math.min(Math.max(dt, 0), 0.05) / Math.max(seconds, 0.001));
    return [current[0] + (target[0] - current[0]) * alpha, current[1] + (target[1] - current[1]) * alpha];
}

function compile(gl, type, source) {
    const shader = gl.createShader(type);
    if (!shader) throw new Error('Could not create shader.');
    gl.shaderSource(shader, source);
    gl.compileShader(shader);
    if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
        const log = gl.getShaderInfoLog(shader);
        gl.deleteShader(shader);
        throw new Error(`Shader compile error: ${log}`);
    }
    return shader;
}

export function createRenderer(canvas, slot) {
    const gl = canvas.getContext('webgl2', {
        alpha: false, antialias: false, depth: false, stencil: false, powerPreference: 'low-power',
    });
    if (!gl) throw new Error('WebGL2 unavailable.');

    const info = gl.getExtension('WEBGL_debug_renderer_info');
    const name = String((info && gl.getParameter(info.UNMASKED_RENDERER_WEBGL)) || gl.getParameter(gl.RENDERER) || '');
    if (SOFTWARE_RENDERERS.test(name)) throw new Error(`Software renderer: ${name}`);

    const vert = compile(gl, gl.VERTEX_SHADER, vertexSource);
    const frag = compile(gl, gl.FRAGMENT_SHADER, fragmentSource);
    const program = gl.createProgram();
    if (!program) throw new Error('Could not create program.');
    gl.attachShader(program, vert);
    gl.attachShader(program, frag);
    gl.linkProgram(program);
    gl.deleteShader(vert);
    gl.deleteShader(frag);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
        const log = gl.getProgramInfoLog(program);
        gl.deleteProgram(program);
        throw new Error(`Program link error: ${log}`);
    }

    gl.useProgram(program);
    gl.bindVertexArray(gl.createVertexArray());
    gl.disable(gl.DEPTH_TEST);
    gl.enable(gl.BLEND);
    gl.blendFuncSeparate(gl.ONE, gl.ONE, gl.ONE, gl.ONE);

    const u = (name) => gl.getUniformLocation(program, name);
    const uniforms = {
        logoCentre: u('uLogoCentre'), logoScale: u('uLogoScale'),
        resolution: u('uResolution'), pointer: u('uPointer'),
        waves: u('uWave[0]'), hoverWave: u('uHoverWave'), misc: u('uMisc'),
    };

    let width = 1, height = 1;
    let logoCentre = [0.5, 0.5], logoScale = [0.2, 0.2];
    let pointer, smoothedPointer = [0.5, 0.5], pointerHold = 0;
    let hoverWave = -1;
    const waves = new Float32Array(MAX_WAVES).fill(-1);
    let startTime = 0, lastRender = -Infinity, lastFrameTime = 0;
    let frameId = 0, disposed = false, painted = false;
    let observer;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const api = { dispose, onFirstFrame: undefined };

    const schedule = () => { if (!disposed && !document.hidden && !frameId) frameId = requestAnimationFrame(draw); };
    const redraw = () => { lastRender = -Infinity; schedule(); };

    const measure = () => {
        const b = canvas.getBoundingClientRect();
        if (b.width <= 0 || b.height <= 0) return;
        const ratio = Math.min(window.devicePixelRatio || 1, MAX_PIXEL_RATIO, MAX_RENDER_WIDTH / b.width);
        const nw = Math.max(1, Math.floor(b.width * ratio));
        const nh = Math.max(1, Math.floor(b.height * ratio));
        if (nw !== width || nh !== height) { width = nw; height = nh; canvas.width = width; canvas.height = height; gl.viewport(0, 0, width, height); }
        const s = slot.getBoundingClientRect();
        logoCentre = [(s.left + s.width / 2 - b.left) / Math.max(1, b.width), (s.top + s.height / 2 - b.top) / Math.max(1, b.height)];
        logoScale = [Math.max(0.001, s.width / Math.max(1, b.width)), Math.max(0.001, s.height / Math.max(1, b.height))];
        redraw();
    };

    const onPointerMove = (e) => {
        if (e.pointerType === 'touch') return;
        const b = canvas.getBoundingClientRect();
        pointer = [Math.min(1, Math.max(0, (e.clientX - b.left) / Math.max(1, b.width))), Math.min(1, Math.max(0, (e.clientY - b.top) / Math.max(1, b.height)))];
    };
    const onPointerLeave = () => { pointer = undefined; };
    const onClick = () => {
        let i = waves.findIndex(p => p < 0);
        if (i < 0) i = waves.indexOf(Math.max(...waves));
        waves[i] = 0; redraw();
    };
    const onPointerEnter = (e) => { if (e.pointerType !== 'touch') { hoverWave = 0; redraw(); } };
    const onContextLost = (e) => { e.preventDefault(); dispose(); canvas.remove(); };
    const onVisibility = () => { if (document.hidden) { cancelAnimationFrame(frameId); frameId = 0; } else redraw(); };

    function draw(now) {
        frameId = 0;
        if (disposed || document.hidden) return;
        if (now - lastRender < FRAME_INTERVAL_MS) { schedule(); return; }
        lastRender = now;
        const time = (now - startTime) / 1000;
        const dt = Math.min(Math.max(time - lastFrameTime, 0), 0.05);
        lastFrameTime = time;
        smoothedPointer = follow(smoothedPointer, pointer || smoothedPointer, dt, POINTER_FOLLOW_SECONDS);
        pointerHold += ((pointer ? 1 : 0) - pointerHold) * (1 - Math.exp(-dt / POINTER_HOLD_SECONDS));
        for (let i = 0; i < waves.length; i++) { if (waves[i] < 0) continue; waves[i] += dt * WAVE_RATE; if (waves[i] >= 1) waves[i] = -1; }
        if (hoverWave >= 0) { hoverWave += dt * WAVE_RATE; if (hoverWave >= 1) hoverWave = -1; }
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
        if (!painted) { painted = true; api.onFirstFrame?.(); }
        if (!reducedMotion.matches) schedule();
    }

    function dispose() {
        if (disposed) return;
        disposed = true;
        if (frameId) cancelAnimationFrame(frameId);
        observer?.disconnect();
        window.removeEventListener('pointermove', onPointerMove);
        document.documentElement.removeEventListener('pointerleave', onPointerLeave);
        document.removeEventListener('visibilitychange', onVisibility);
        slot.removeEventListener('click', onClick);
        slot.removeEventListener('pointerenter', onPointerEnter);
        canvas.removeEventListener('webglcontextlost', onContextLost);
        gl.deleteProgram(program);
    }

    startTime = performance.now();
    observer = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(measure) : undefined;
    observer?.observe(canvas);
    observer?.observe(slot);
    window.addEventListener('pointermove', onPointerMove, { passive: true });
    document.documentElement.addEventListener('pointerleave', onPointerLeave);
    document.addEventListener('visibilitychange', onVisibility);
    slot.addEventListener('click', onClick);
    slot.addEventListener('pointerenter', onPointerEnter);
    canvas.addEventListener('webglcontextlost', onContextLost);
    measure();
    return api;
}
