//#region src/shader/vert.glsl.js
var e = .55, t = /swiftshader|llvmpipe|softpipe|software|basic render/i;
function n(e, t, n, r) {
	let i = 1 - Math.exp(-Math.min(Math.max(n, 0), .05) / Math.max(r, .001));
	return [e[0] + (t[0] - e[0]) * i, e[1] + (t[1] - e[1]) * i];
}
function r(e, t, n) {
	let r = e.createShader(t);
	if (!r) throw Error("Could not create shader.");
	if (e.shaderSource(r, n), e.compileShader(r), !e.getShaderParameter(r, e.COMPILE_STATUS)) {
		let t = e.getShaderInfoLog(r);
		throw e.deleteShader(r), Error(`Shader compile error: ${t}`);
	}
	return r;
}
function i(i, a) {
	let o = i.getContext("webgl2", {
		alpha: !1,
		antialias: !1,
		depth: !1,
		stencil: !1,
		powerPreference: "low-power"
	});
	if (!o) throw Error("WebGL2 unavailable.");
	let s = o.getExtension("WEBGL_debug_renderer_info"), c = String(s && o.getParameter(s.UNMASKED_RENDERER_WEBGL) || o.getParameter(o.RENDERER) || "");
	if (t.test(c)) throw Error(`Software renderer: ${c}`);
	let l = r(o, o.VERTEX_SHADER, "#version 300 es\nprecision highp float;\n\nuniform vec2 uLogoCentre;\nuniform vec2 uLogoScale;\nuniform vec2 uResolution;\nuniform vec2 uPointer;\nuniform float uWave[8];\nuniform float uHoverWave;\nuniform vec2 uMisc;\n\nout vec2 vLocal;\nout vec4 vTint;\n\nconst vec3 LARAVEL_BASE  = vec3(0.72, 0.06, 0.04);\nconst vec3 LARAVEL_MID   = vec3(1.00, 0.18, 0.09);\nconst vec3 LARAVEL_CREST = vec3(1.00, 0.48, 0.28);\nconst vec3 LARAVEL_TIP   = vec3(1.00, 0.78, 0.58);\n\nconst uint COLS = 704u;\nconst uint ROWS = 260u;\n\nconst float Z_NEAR  = 1.3;\nconst float Z_FAR   = 26.0;\nconst float CAM_Y   = 3.8;\nconst float FOCAL   = 1.3;\nconst float HORIZON = -0.05;\n\nconst float PEAK1_X = -0.38;\nconst float PEAK1_H =  4.2;\nconst float PEAK2_X =  0.44;\nconst float PEAK2_H =  2.8;\nconst float PEAK3_X = -0.82;\nconst float PEAK3_H =  1.8;\n\nconst vec2 QUAD[6] = vec2[6](\n  vec2(-1.0,-1.0),vec2(1.0,-1.0),vec2(-1.0,1.0),\n  vec2(-1.0,1.0),vec2(1.0,-1.0),vec2(1.0,1.0)\n);\n\nuint scramble(uint v){\n  v^=v>>16u; v*=0x7feb352du; v^=v>>15u; v*=0x846ca68bu; return v^(v>>16u);\n}\nfloat sampleKey(vec2 cell){\n  uvec2 k=uvec2(ivec2(cell));\n  uint m=scramble(k.x^scramble(k.y+0x9e3779b9u));\n  return float(m)/4294967295.0;\n}\nfloat valueField(vec2 p){\n  vec2 i=floor(p); vec2 f=fract(p);\n  vec2 u=f*f*f*(f*(f*6.0-15.0)+10.0);\n  float a=sampleKey(i),b=sampleKey(i+vec2(1,0)),c=sampleKey(i+vec2(0,1)),d=sampleKey(i+vec2(1));\n  return mix(mix(a,b,u.x),mix(c,d,u.x),u.y);\n}\nfloat terrain(float x,float z,float u,float shape){\n  vec2 q=vec2(x*0.30,z*0.085);\n  mat2 rot=mat2(1.6,1.2,-1.2,1.6);\n  float r=0.0,amp=0.55;\n  for(int i=0;i<5;i++){r+=amp*(1.0-abs(2.0*valueField(q)-1.0));q=rot*q;amp*=0.55;}\n  float h=pow(max(r,0.0),1.5)*2.6;\n  h*=0.70+0.60*valueField(vec2(x*0.05,z*0.05)+7.0);\n  float rise=smoothstep(3.0,9.0,z);\n  h+=rise*PEAK1_H*max(0.0,1.0-abs(u-PEAK1_X)*3.2);\n  h+=rise*PEAK2_H*max(0.0,1.0-abs(u-PEAK2_X)*3.6);\n  h+=rise*PEAK3_H*max(0.0,1.0-abs(u-PEAK3_X)*2.8);\n  return max(h,0.0)*shape;\n}\nfloat shockwave(float phase,float dist){\n  if(phase<0.0)return 0.0;\n  float radius=phase*1.7,width=0.09+phase*0.20;\n  return exp(-((dist-radius)*(dist-radius))/(width*width))\n    *(1.0-smoothstep(0.55,1.0,phase))*smoothstep(0.0,0.04,phase);\n}\n\nvoid main(){\n  uint id=uint(gl_VertexID)/6u; int corner=gl_VertexID%6;\n  float t=uMisc.x;\n  vec2 res=max(uResolution,vec2(1.0));\n  float aspect=max(res.x/res.y,0.5);\n  float shape=clamp(aspect/1.6,0.5,1.0);\n  float tanX=max(aspect,0.95)/FOCAL;\n\n  uint col=id%COLS,row=id/COLS;\n  uint symCol=min(col,COLS-1u-col);\n  vec2 seed=vec2(float(symCol),float(row));\n  float jX=sampleKey(seed)-0.5,jY=sampleKey(seed+17.0)-0.5,rnd=sampleKey(seed+41.0);\n  float mJX=col<COLS/2u?jX:-jX;\n  float u=((float(col)+mJX)/float(COLS-1u))*2.3-1.15;\n  float vFr=clamp((float(row)+jY)/float(ROWS),0.0,1.0);\n  float z=mix(Z_NEAR,Z_FAR,pow(vFr,1.5));\n  float wx=u*z*tanX;\n\n  float h=terrain(wx,z,u,shape);\n  h+=(valueField(vec2(wx*1.6,z*1.6))-0.5)*0.05*shape;\n  float e=0.22;\n  float hR=terrain(wx+e,z,u,shape),hL=terrain(wx-e,z,u,shape),hF=terrain(wx,z+e,u,shape);\n  vec3 N=normalize(vec3(-(hR-hL)/(2.0*e),1.0,-(hF-h)/e));\n  vec3 L=normalize(vec3(-0.12,0.60,-0.42));\n  float diffuse=pow(max(dot(N,L),0.0),1.3);\n  float crest=smoothstep(0.0,1.5,(2.0*h-hR-hL)/(e*e)*0.2);\n  float sy=(h-CAM_Y)/z*FOCAL+HORIZON;\n\n  float occluded=0.0;\n  for(int k=1;k<=4;k++){\n    float sz=Z_NEAR+(z-Z_NEAR)*float(k)/5.0;\n    float sh=terrain(u*sz*tanX,sz,u,shape);\n    occluded=max(occluded,step(sy+0.012,(sh-CAM_Y)/sz*FOCAL+HORIZON));\n  }\n\n  vec2 centre=vec2(uLogoCentre.x*2.0-1.0,1.0-2.0*uLogoCentre.y);\n  float logoH=max(uLogoScale.y,0.01);\n  vec2 toCentre=vec2((u-centre.x)*aspect,sy-centre.y);\n  float dist=length(toCentre);\n  vec2 clearCoord=abs(toCentre/vec2(logoH*0.9,logoH*0.9));\n  float clearEdge=pow(pow(clearCoord.x,3.0)+pow(clearCoord.y,3.0),1.0/3.0);\n  float clearing=smoothstep(0.5,1.2,clearEdge);\n\n  float rpPhase=fract(t*0.30),rpRadius=rpPhase*2.4,rpWidth=0.16+rpPhase*0.25;\n  float ripple=exp(-((dist-rpRadius)*(dist-rpRadius))/(rpWidth*rpWidth))\n    *(1.0-smoothstep(0.55,0.95,rpPhase))*smoothstep(0.0,0.05,rpPhase);\n\n  float swPhase=fract(t*0.11),swRadius=swPhase*(aspect+0.18),swWidth=0.035+swPhase*0.025;\n  float swDist=abs((u-centre.x)*aspect)-swRadius;\n  float sweep=exp(-(swDist*swDist)/(swWidth*swWidth))\n    *(1.0-smoothstep(aspect*0.88,aspect+0.18,swRadius));\n\n  float shock=0.0;\n  for(int i=0;i<8;i++)shock+=shockwave(uWave[i],dist);\n  shock+=shockwave(uHoverWave,dist)*0.25;\n\n  float fog=exp(-(z-Z_NEAR)*0.032)*(1.0-smoothstep(0.90,1.0,vFr));\n  float nearFade=smoothstep(Z_NEAR,1.6,z);\n  float shimmer=0.88+0.12*sin(t*(0.7+rnd*1.4)+rnd*31.0);\n  float grain=0.88+0.24*valueField(vec2(wx*3.0,z*3.0));\n  float pulseRnd=step(0.82,sampleKey(seed+63.0));\n  float pulse=pow(0.5+0.5*sin(t*(0.5+rnd*1.1)+rnd*61.0),6.0);\n  float noiseWave=1.0+(valueField(vec2(wx*0.22-t*0.20,z*0.22))-0.5)*0.24\n                    +(valueField(vec2(wx*0.55+t*0.09,z*0.55-t*0.14))-0.5)*0.16;\n\n  float lit=diffuse*0.45+crest*(0.75+diffuse*0.65);\n  float brightness=(0.065+lit*grain)*fog*nearFade*noiseWave*shimmer\n    *(1.0+pulse*(0.18+pulseRnd*1.1))*(1.0+ripple*0.75+shock*3.0+sweep*2.4);\n\n  float colMix=clamp(crest+pulse*pulseRnd*0.35+sweep*0.8,0.0,1.0);\n  vec3 tint=mix(LARAVEL_BASE,LARAVEL_MID,smoothstep(0.0,0.5,colMix));\n  tint=mix(tint,LARAVEL_CREST,smoothstep(0.4,1.0,colMix));\n  tint*=brightness;\n  float glint=step(0.993,rnd)*diffuse*(0.5+0.5*sin(t*2.0+rnd*47.0));\n  tint+=LARAVEL_TIP*glint*fog*(0.55+sweep*2.0);\n\n  vec2 ptr=vec2((uPointer.x-0.5)*2.0,(0.5-uPointer.y)*2.0);\n  vec2 toPtr=vec2((u-ptr.x)*aspect,sy-ptr.y);\n  float excite=(1.0-smoothstep(0.0,0.55,length(toPtr)))*uMisc.y;\n  vec2 push=normalize(toPtr+vec2(0.0001,0.0))*excite*(0.006+0.010*rnd);\n  tint*=1.0+excite*1.3;\n\n  float floatAmp=(0.0015+(1.0-vFr)*0.006)*(1.0+excite*2.5);\n  vec2 drift=vec2(sin(t*(0.35+rnd*0.50)+rnd*40.0),cos(t*(0.28+rnd*0.45)+rnd*23.0)*1.4)*floatAmp;\n  vec2 shockPush=vec2(toCentre.x/max(dist,0.02)/aspect,toCentre.y/max(dist,0.02))*shock*0.045;\n  float sizePx=clamp(16.0/z,0.65,1.95)*(0.75+0.5*sampleKey(seed+27.0))\n    *(1.0+glint*0.25+ripple*0.20+sweep*0.55+shock*0.75);\n\n  vec2 offset=QUAD[corner];\n  gl_Position=vec4(vec2(u,sy)+drift+push+shockPush+offset*sizePx*2.0/res,0.5,1.0);\n  vLocal=offset;\n  vTint=vec4(tint*(1.0-occluded)*clearing,1.0);\n}"), u = r(o, o.FRAGMENT_SHADER, "#version 300 es\nprecision highp float;\nin vec2 vLocal;\nin vec4 vTint;\nout vec4 fragColor;\nvoid main(){\n  float falloff=1.0-smoothstep(0.38,0.90,length(vLocal));\n  fragColor=vec4(vTint.rgb*falloff,1.0);\n}"), d = o.createProgram();
	if (!d) throw Error("Could not create program.");
	if (o.attachShader(d, l), o.attachShader(d, u), o.linkProgram(d), o.deleteShader(l), o.deleteShader(u), !o.getProgramParameter(d, o.LINK_STATUS)) {
		let e = o.getProgramInfoLog(d);
		throw o.deleteProgram(d), Error(`Program link error: ${e}`);
	}
	o.useProgram(d), o.bindVertexArray(o.createVertexArray()), o.disable(o.DEPTH_TEST), o.enable(o.BLEND), o.blendFuncSeparate(o.ONE, o.ONE, o.ONE, o.ONE);
	let f = (e) => o.getUniformLocation(d, e), p = {
		logoCentre: f("uLogoCentre"),
		logoScale: f("uLogoScale"),
		resolution: f("uResolution"),
		pointer: f("uPointer"),
		waves: f("uWave[0]"),
		hoverWave: f("uHoverWave"),
		misc: f("uMisc")
	}, m = 1, h = 1, g = [.5, .5], _ = [.2, .2], v, y = [.5, .5], b = 0, x = -1, S = (/* @__PURE__ */ new Float32Array(8)).fill(-1), C = 0, w = -Infinity, T = 0, E = 0, D = !1, O = !1, k, A = window.matchMedia("(prefers-reduced-motion: reduce)"), j = {
		dispose: H,
		onFirstFrame: void 0
	}, M = () => {
		!D && !document.hidden && !E && (E = requestAnimationFrame(V));
	}, N = () => {
		w = -Infinity, M();
	}, P = () => {
		let e = i.getBoundingClientRect();
		if (e.width <= 0 || e.height <= 0) return;
		let t = Math.min(window.devicePixelRatio || 1, 1.6, 2400 / e.width), n = Math.max(1, Math.floor(e.width * t)), r = Math.max(1, Math.floor(e.height * t));
		(n !== m || r !== h) && (m = n, h = r, i.width = m, i.height = h, o.viewport(0, 0, m, h));
		let s = a.getBoundingClientRect();
		g = [(s.left + s.width / 2 - e.left) / Math.max(1, e.width), (s.top + s.height / 2 - e.top) / Math.max(1, e.height)], _ = [Math.max(.001, s.width / Math.max(1, e.width)), Math.max(.001, s.height / Math.max(1, e.height))], N();
	}, F = (e) => {
		if (e.pointerType === "touch") return;
		let t = i.getBoundingClientRect();
		v = [Math.min(1, Math.max(0, (e.clientX - t.left) / Math.max(1, t.width))), Math.min(1, Math.max(0, (e.clientY - t.top) / Math.max(1, t.height)))];
	}, I = () => {
		v = void 0;
	}, L = () => {
		let e = S.findIndex((e) => e < 0);
		e < 0 && (e = S.indexOf(Math.max(...S))), S[e] = 0, N();
	}, R = (e) => {
		e.pointerType !== "touch" && (x = 0, N());
	}, z = (e) => {
		e.preventDefault(), H(), i.remove();
	}, B = () => {
		document.hidden ? (cancelAnimationFrame(E), E = 0) : N();
	};
	function V(t) {
		if (E = 0, D || document.hidden) return;
		if (t - w < 33.333333333333336) {
			M();
			return;
		}
		w = t;
		let r = (t - C) / 1e3, i = Math.min(Math.max(r - T, 0), .05);
		T = r, y = n(y, v || y, i, .22), b += (+!!v - b) * (1 - Math.exp(-i / .35));
		for (let t = 0; t < S.length; t++) S[t] < 0 || (S[t] += i * e, S[t] >= 1 && (S[t] = -1));
		x >= 0 && (x += i * e, x >= 1 && (x = -1)), o.useProgram(d), o.uniform2f(p.logoCentre, g[0], g[1]), o.uniform2f(p.logoScale, _[0], _[1]), o.uniform2f(p.resolution, m, h), o.uniform2f(p.pointer, y[0], y[1]), o.uniform1fv(p.waves, S), o.uniform1f(p.hoverWave, x), o.uniform2f(p.misc, r, b), o.clearColor(0, 0, 0, 1), o.clear(o.COLOR_BUFFER_BIT), o.drawArrays(o.TRIANGLES, 0, 1098240), O || (O = !0, j.onFirstFrame?.()), A.matches || M();
	}
	function H() {
		D || (D = !0, E && cancelAnimationFrame(E), k?.disconnect(), window.removeEventListener("pointermove", F), document.documentElement.removeEventListener("pointerleave", I), document.removeEventListener("visibilitychange", B), a.removeEventListener("click", L), a.removeEventListener("pointerenter", R), i.removeEventListener("webglcontextlost", z), o.deleteProgram(d));
	}
	return C = performance.now(), k = typeof ResizeObserver < "u" ? new ResizeObserver(P) : void 0, k?.observe(i), k?.observe(a), window.addEventListener("pointermove", F, { passive: !0 }), document.documentElement.addEventListener("pointerleave", I), document.addEventListener("visibilitychange", B), a.addEventListener("click", L), a.addEventListener("pointerenter", R), i.addEventListener("webglcontextlost", z), P(), j;
}
//#endregion
//#region src/index.js
function a() {
	let e = document.querySelector("[data-welcomepage-canvas]"), t = document.querySelector("[data-welcomepage-slot]");
	if (e instanceof HTMLCanvasElement && t) {
		if (!window.WebGL2RenderingContext) e.remove();
		else try {
			let n = i(e, t);
			n.onFirstFrame = () => {
				e.style.opacity = "1";
			}, window.addEventListener("pagehide", n.dispose, { once: !0 });
		} catch {
			e.remove();
		}
	}
}
document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", a, { once: !0 }) : a();
//#endregion
export { a as boot };
