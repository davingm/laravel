#version 300 es
precision highp float;

in vec2 vLocal;
in vec4 vTint;
out vec4 fragColor;

void main() {
  float falloff = 1.0 - smoothstep(0.42, 1.0, length(vLocal));
  fragColor = vec4(vTint.rgb * falloff, 1.0);
}
