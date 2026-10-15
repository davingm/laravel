#version 300 es
precision highp float;

uniform vec2 uLogoCentre;
uniform vec2 uLogoScale;
uniform vec2 uResolution;
uniform vec2 uPointer;
uniform float uWave[8];
uniform float uHoverWave;
uniform vec2 uMisc;

out vec2 vLocal;
out vec4 vTint;

const vec3 LARAVEL_RED = vec3(1.0, 0.12, 0.075);
const vec3 LARAVEL_PALE = vec3(1.0, 0.48, 0.34);
const vec3 LARAVEL_GLOW = vec3(1.0, 0.72, 0.53);

const uint COLS = 520u;
const uint ROWS = 190u;
const uint TERRAIN_VERTICES = COLS * ROWS * 6u;

const float Z_NEAR = 1.3;
const float Z_FAR = 26.0;
const float CAM_Y = 2.4;
const float FOCAL = 1.3;
const float HORIZON = 0.30;
const float PEAK_X = 0.38;
const float PEAK_HEIGHT = 2.7;

const vec2 QUAD[6] = vec2[6](
  vec2(-1.0, -1.0), vec2(1.0, -1.0), vec2(-1.0, 1.0),
  vec2(-1.0, 1.0), vec2(1.0, -1.0), vec2(1.0, 1.0)
);

uint scramble(uint value) {
  value ^= value >> 16u;
  value *= 0x7feb352du;
  value ^= value >> 15u;
  value *= 0x846ca68bu;
  return value ^ (value >> 16u);
}

float sampleKey(vec2 cell) {
  uvec2 key = uvec2(ivec2(cell));
  uint mixed = scramble(key.x ^ scramble(key.y + 0x9e3779b9u));
  return float(mixed) / 4294967295.0;
}

float valueField(vec2 position) {
  vec2 cell = floor(position);
  vec2 blend = fract(position);
  blend = blend * blend * blend * (blend * (blend * 6.0 - 15.0) + 10.0);
  float lower = mix(sampleKey(cell), sampleKey(cell + vec2(1.0, 0.0)), blend.x);
  float upper = mix(sampleKey(cell + vec2(0.0, 1.0)), sampleKey(cell + vec2(1.0)), blend.x);
  return mix(lower, upper, blend.y);
}

float summit(float coordinate, float centre, float width) {
  float distanceFromPeak = (abs(coordinate) - centre) / width;
  return exp(-distanceFromPeak * distanceFromPeak * 1.7);
}

float shockwave(float phase, float dist) {
  if (phase < 0.0) return 0.0;
  float radius = phase * 1.7;
  float width = 0.09 + phase * 0.20;
  return exp(-((dist - radius) * (dist - radius)) / (width * width))
    * (1.0 - smoothstep(0.55, 1.0, phase))
    * smoothstep(0.0, 0.04, phase);
}

float terrain(float x, float z, float u, float shape) {
  vec2 field = vec2(abs(x), z);
  float broad = valueField(field * vec2(0.11, 0.12));
  float middle = valueField(field * vec2(0.29, 0.31) + 13.7);
  float detail = valueField(field * vec2(0.78, 0.72) - 5.2);
  float ridge = 1.0 - abs(2.0 * broad - 1.0);
  ridge = ridge * 0.68 + (1.0 - abs(2.0 * middle - 1.0)) * 0.24;
  ridge += detail * 0.08;
  float height = pow(max(ridge, 0.0), 1.35) * 2.45;
  height *= 0.78 + 0.44 * valueField(field * 0.055 + 7.0);
  float rise = smoothstep(3.0, 9.0, z);
  float skyline = summit(u, PEAK_X, 0.15) * 1.12
    + summit(u, 0.73, 0.22) * 0.48
    + summit(u, 0.06, 0.20) * 0.24;
  height += rise * PEAK_HEIGHT * skyline;
  return max(height, 0.0) * shape;
}

void main() {
  uint id = uint(gl_VertexID) / 6u;
  int corner = gl_VertexID % 6;
  float time = uMisc.x;
  vec2 resolution = max(uResolution, vec2(1.0));
  float aspect = max(resolution.x / resolution.y, 0.5);
  float shape = clamp(aspect / 1.6, 0.5, 1.0);
  float tanX = max(aspect, 0.95) / FOCAL;

  uint col = id % COLS;
  uint row = id / COLS;
  uint symmetricCol = min(col, COLS - 1u - col);
  vec2 seed = vec2(float(symmetricCol), float(row));
  float jitterX = sampleKey(seed) - 0.5;
  float jitterY = sampleKey(seed + 17.0) - 0.5;
  float random = sampleKey(seed + 41.0);

  float mirroredJitter = col < COLS / 2u ? jitterX : -jitterX;
  float u = ((float(col) + mirroredJitter) / float(COLS - 1u)) * 2.3 - 1.15;
  float depthFraction = clamp((float(row) + jitterY) / float(ROWS), 0.0, 1.0);
  float z = mix(Z_NEAR, Z_FAR, pow(depthFraction, 1.5));
  float worldX = u * z * tanX;

  float height = terrain(worldX, z, u, shape);
  height += (valueField(vec2(worldX * 1.6, z * 1.6)) - 0.5) * 0.05 * shape;

  float e = 0.22;
  float heightRight = terrain(worldX + e, z, u, shape);
  float heightLeft = terrain(worldX - e, z, u, shape);
  float heightFar = terrain(worldX, z + e, u, shape);
  vec3 normal = normalize(vec3(
    -(heightRight - heightLeft) / (2.0 * e),
    1.0,
    -(heightFar - height) / e
  ));
  vec3 lightDirection = normalize(vec3(-0.12, 0.60, -0.42));
  float diffuse = pow(max(dot(normal, lightDirection), 0.0), 1.3);
  float crest = smoothstep(0.0, 1.5, (2.0 * height - heightRight - heightLeft) / (e * e) * 0.2);
  float screenY = (height - CAM_Y) / z * FOCAL + HORIZON;

  float occluded = 0.0;
  for (int stepIndex = 1; stepIndex <= 4; stepIndex++) {
    float sampleZ = Z_NEAR + (z - Z_NEAR) * float(stepIndex) / 5.0;
    float sampleHeight = terrain(u * sampleZ * tanX, sampleZ, u, shape);
    occluded = max(occluded, step(screenY + 0.012, (sampleHeight - CAM_Y) / sampleZ * FOCAL + HORIZON));
  }

  vec2 centre = vec2(uLogoCentre.x * 2.0 - 1.0, 1.0 - 2.0 * uLogoCentre.y);
  vec2 toCentre = vec2((u - centre.x) * aspect, screenY - centre.y);
  float centreDistance = length(toCentre);
  vec2 clearSize = max(vec2(uLogoScale.x * 0.68, uLogoScale.y * 0.82), vec2(0.065));
  vec2 clear = abs(toCentre / clearSize);
  float clearEdge = pow(pow(clear.x, 3.0) + pow(clear.y, 3.0), 1.0 / 3.0);
  float clearing = smoothstep(0.82, 1.42, clearEdge);

  float ripplePhase = fract(time * 0.30);
  float rippleRadius = ripplePhase * 2.4;
  float rippleWidth = 0.16 + ripplePhase * 0.25;
  float ripple = exp(-((centreDistance - rippleRadius) * (centreDistance - rippleRadius)) / (rippleWidth * rippleWidth))
    * (1.0 - smoothstep(0.55, 0.95, ripplePhase))
    * smoothstep(0.0, 0.05, ripplePhase);

  float sweepPhase = fract(time * 0.11);
  float sweepRadius = sweepPhase * (aspect + 0.18);
  float sweepWidth = 0.035 + sweepPhase * 0.025;
  float sweepDistance = abs((u - centre.x) * aspect) - sweepRadius;
  float sweep = exp(-(sweepDistance * sweepDistance) / (sweepWidth * sweepWidth))
    * (1.0 - smoothstep(aspect * 0.88, aspect + 0.18, sweepRadius));

  float shock = 0.0;
  for (int waveIndex = 0; waveIndex < 8; waveIndex++) {
    shock += shockwave(uWave[waveIndex], centreDistance);
  }
  shock += shockwave(uHoverWave, centreDistance) * 0.25;

  float fog = exp(-(z - Z_NEAR) * 0.075) * (1.0 - smoothstep(0.86, 1.0, depthFraction));
  float nearFade = smoothstep(Z_NEAR, 2.6, z);
  float shimmer = 0.84 + 0.16 * sin(time * (0.7 + random * 1.4) + random * 31.0);
  float grain = 0.88 + 0.24 * valueField(vec2(worldX * 3.0, z * 3.0));
  float pulse = pow(0.5 + 0.5 * sin(time * (0.5 + random * 1.1) + random * 61.0), 6.0);
  float pulses = step(0.82, sampleKey(seed + 63.0));
  float noiseWave = 1.0 + (valueField(vec2(worldX * 0.22 - time * 0.20, z * 0.22)) - 0.5) * 0.24;

  float lit = diffuse * 0.20 + crest * (0.38 + diffuse * 0.50);
  float brightness = (0.025 + lit * grain) * fog * nearFade * noiseWave * shimmer
    * (1.0 + pulse * (0.18 + pulses * 1.1))
    * (1.0 + ripple * 0.75 + shock * 3.0 + sweep * 2.4);
  vec3 tint = mix(LARAVEL_RED * 0.68, LARAVEL_PALE, clamp(crest + pulse * pulses * 0.35 + sweep * 0.8, 0.0, 1.0));
  tint *= brightness;
  float glint = step(0.991, random) * diffuse * (0.5 + 0.5 * sin(time * 2.0 + random * 47.0));
  tint += LARAVEL_GLOW * glint * fog * (0.5 + sweep * 2.0);

  vec2 pointer = vec2((uPointer.x - 0.5) * 2.0, (0.5 - uPointer.y) * 2.0);
  vec2 toPointer = vec2((u - pointer.x) * aspect, screenY - pointer.y);
  float excite = (1.0 - smoothstep(0.0, 0.55, length(toPointer))) * uMisc.y;
  vec2 push = normalize(toPointer + vec2(0.0001, 0.0)) * excite * (0.006 + 0.010 * random);
  tint *= 1.0 + excite * 1.3;

  float floatAmplitude = (0.0015 + (1.0 - depthFraction) * 0.006) * (1.0 + excite * 2.5);
  vec2 drift = vec2(
    sin(time * (0.35 + random * 0.5) + random * 40.0),
    cos(time * (0.28 + random * 0.45) + random * 23.0) * 1.4
  ) * floatAmplitude;
  vec2 shockPush = vec2(toCentre.x / max(centreDistance, 0.02) / aspect, toCentre.y / max(centreDistance, 0.02)) * shock * 0.045;
  float sizePx = clamp(22.0 / z, 0.9, 2.35)
    * (0.75 + 0.5 * sampleKey(seed + 27.0))
    * (1.0 + glint * 0.3 + ripple * 0.2 + sweep * 0.55 + shock * 0.75);

  vec2 offset = QUAD[corner];
  gl_Position = vec4(vec2(u, screenY) + drift + push + shockPush + offset * sizePx * 2.0 / resolution, 0.5, 1.0);
  vLocal = offset;
  vTint = vec4(tint * (1.0 - occluded) * clearing, 1.0);
}
