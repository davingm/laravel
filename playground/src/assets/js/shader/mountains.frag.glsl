#version 300 es
precision highp float;

in vec2 vLocal;
in vec4 vTint;
out vec4 fragColor;

void main() {
  // Slightly softer falloff than the original (0.38→0.90 vs 0.42→1.0)
  // so particles bleed into each other just enough to form solid ridgelines
  // without haloing on flat areas.
  float falloff = 1.0 - smoothstep(0.38, 0.90, length(vLocal));
  fragColor = vec4(vTint.rgb * falloff, 1.0);
}
