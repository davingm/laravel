export default /* glsl */`#version 300 es
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

const vec3 LARAVEL_BASE  = vec3(0.72, 0.06, 0.04);
const vec3 LARAVEL_MID   = vec3(1.00, 0.18, 0.09);
const vec3 LARAVEL_CREST = vec3(1.00, 0.48, 0.28);
const vec3 LARAVEL_TIP   = vec3(1.00, 0.78, 0.58);

const uint COLS = 704u;
const uint ROWS = 260u;

const float Z_NEAR  = 1.3;
const float Z_FAR   = 26.0;
const float CAM_Y   = 3.8;
const float FOCAL   = 1.3;
const float HORIZON = -0.05;

const float PEAK1_X = -0.38;
const float PEAK1_H =  4.2;
const float PEAK2_X =  0.44;
const float PEAK2_H =  2.8;
const float PEAK3_X = -0.82;
const float PEAK3_H =  1.8;

const vec2 QUAD[6] = vec2[6](
  vec2(-1.0,-1.0),vec2(1.0,-1.0),vec2(-1.0,1.0),
  vec2(-1.0,1.0),vec2(1.0,-1.0),vec2(1.0,1.0)
);

uint scramble(uint v){
  v^=v>>16u; v*=0x7feb352du; v^=v>>15u; v*=0x846ca68bu; return v^(v>>16u);
}
float sampleKey(vec2 cell){
  uvec2 k=uvec2(ivec2(cell));
  uint m=scramble(k.x^scramble(k.y+0x9e3779b9u));
  return float(m)/4294967295.0;
}
float valueField(vec2 p){
  vec2 i=floor(p); vec2 f=fract(p);
  vec2 u=f*f*f*(f*(f*6.0-15.0)+10.0);
  float a=sampleKey(i),b=sampleKey(i+vec2(1,0)),c=sampleKey(i+vec2(0,1)),d=sampleKey(i+vec2(1));
  return mix(mix(a,b,u.x),mix(c,d,u.x),u.y);
}
float terrain(float x,float z,float u,float shape){
  vec2 q=vec2(x*0.30,z*0.085);
  mat2 rot=mat2(1.6,1.2,-1.2,1.6);
  float r=0.0,amp=0.55;
  for(int i=0;i<5;i++){r+=amp*(1.0-abs(2.0*valueField(q)-1.0));q=rot*q;amp*=0.55;}
  float h=pow(max(r,0.0),1.5)*2.6;
  h*=0.70+0.60*valueField(vec2(x*0.05,z*0.05)+7.0);
  float rise=smoothstep(3.0,9.0,z);
  h+=rise*PEAK1_H*max(0.0,1.0-abs(u-PEAK1_X)*3.2);
  h+=rise*PEAK2_H*max(0.0,1.0-abs(u-PEAK2_X)*3.6);
  h+=rise*PEAK3_H*max(0.0,1.0-abs(u-PEAK3_X)*2.8);
  return max(h,0.0)*shape;
}
float shockwave(float phase,float dist){
  if(phase<0.0)return 0.0;
  float radius=phase*1.7,width=0.09+phase*0.20;
  return exp(-((dist-radius)*(dist-radius))/(width*width))
    *(1.0-smoothstep(0.55,1.0,phase))*smoothstep(0.0,0.04,phase);
}

void main(){
  uint id=uint(gl_VertexID)/6u; int corner=gl_VertexID%6;
  float t=uMisc.x;
  vec2 res=max(uResolution,vec2(1.0));
  float aspect=max(res.x/res.y,0.5);
  float shape=clamp(aspect/1.6,0.5,1.0);
  float tanX=max(aspect,0.95)/FOCAL;

  uint col=id%COLS,row=id/COLS;
  uint symCol=min(col,COLS-1u-col);
  vec2 seed=vec2(float(symCol),float(row));
  float jX=sampleKey(seed)-0.5,jY=sampleKey(seed+17.0)-0.5,rnd=sampleKey(seed+41.0);
  float mJX=col<COLS/2u?jX:-jX;
  float u=((float(col)+mJX)/float(COLS-1u))*2.3-1.15;
  float vFr=clamp((float(row)+jY)/float(ROWS),0.0,1.0);
  float z=mix(Z_NEAR,Z_FAR,pow(vFr,1.5));
  float wx=u*z*tanX;

  float h=terrain(wx,z,u,shape);
  h+=(valueField(vec2(wx*1.6,z*1.6))-0.5)*0.05*shape;
  float e=0.22;
  float hR=terrain(wx+e,z,u,shape),hL=terrain(wx-e,z,u,shape),hF=terrain(wx,z+e,u,shape);
  vec3 N=normalize(vec3(-(hR-hL)/(2.0*e),1.0,-(hF-h)/e));
  vec3 L=normalize(vec3(-0.12,0.60,-0.42));
  float diffuse=pow(max(dot(N,L),0.0),1.3);
  float crest=smoothstep(0.0,1.5,(2.0*h-hR-hL)/(e*e)*0.2);
  float sy=(h-CAM_Y)/z*FOCAL+HORIZON;

  float occluded=0.0;
  for(int k=1;k<=4;k++){
    float sz=Z_NEAR+(z-Z_NEAR)*float(k)/5.0;
    float sh=terrain(u*sz*tanX,sz,u,shape);
    occluded=max(occluded,step(sy+0.012,(sh-CAM_Y)/sz*FOCAL+HORIZON));
  }

  vec2 centre=vec2(uLogoCentre.x*2.0-1.0,1.0-2.0*uLogoCentre.y);
  float logoH=max(uLogoScale.y,0.01);
  vec2 toCentre=vec2((u-centre.x)*aspect,sy-centre.y);
  float dist=length(toCentre);
  vec2 clearCoord=abs(toCentre/vec2(logoH*0.9,logoH*0.9));
  float clearEdge=pow(pow(clearCoord.x,3.0)+pow(clearCoord.y,3.0),1.0/3.0);
  float clearing=smoothstep(0.5,1.2,clearEdge);

  float rpPhase=fract(t*0.30),rpRadius=rpPhase*2.4,rpWidth=0.16+rpPhase*0.25;
  float ripple=exp(-((dist-rpRadius)*(dist-rpRadius))/(rpWidth*rpWidth))
    *(1.0-smoothstep(0.55,0.95,rpPhase))*smoothstep(0.0,0.05,rpPhase);

  float swPhase=fract(t*0.11),swRadius=swPhase*(aspect+0.18),swWidth=0.035+swPhase*0.025;
  float swDist=abs((u-centre.x)*aspect)-swRadius;
  float sweep=exp(-(swDist*swDist)/(swWidth*swWidth))
    *(1.0-smoothstep(aspect*0.88,aspect+0.18,swRadius));

  float shock=0.0;
  for(int i=0;i<8;i++)shock+=shockwave(uWave[i],dist);
  shock+=shockwave(uHoverWave,dist)*0.25;

  float fog=exp(-(z-Z_NEAR)*0.032)*(1.0-smoothstep(0.90,1.0,vFr));
  float nearFade=smoothstep(Z_NEAR,1.6,z);
  float shimmer=0.88+0.12*sin(t*(0.7+rnd*1.4)+rnd*31.0);
  float grain=0.88+0.24*valueField(vec2(wx*3.0,z*3.0));
  float pulseRnd=step(0.82,sampleKey(seed+63.0));
  float pulse=pow(0.5+0.5*sin(t*(0.5+rnd*1.1)+rnd*61.0),6.0);
  float noiseWave=1.0+(valueField(vec2(wx*0.22-t*0.20,z*0.22))-0.5)*0.24
                    +(valueField(vec2(wx*0.55+t*0.09,z*0.55-t*0.14))-0.5)*0.16;

  float lit=diffuse*0.45+crest*(0.75+diffuse*0.65);
  float brightness=(0.065+lit*grain)*fog*nearFade*noiseWave*shimmer
    *(1.0+pulse*(0.18+pulseRnd*1.1))*(1.0+ripple*0.75+shock*3.0+sweep*2.4);

  float colMix=clamp(crest+pulse*pulseRnd*0.35+sweep*0.8,0.0,1.0);
  vec3 tint=mix(LARAVEL_BASE,LARAVEL_MID,smoothstep(0.0,0.5,colMix));
  tint=mix(tint,LARAVEL_CREST,smoothstep(0.4,1.0,colMix));
  tint*=brightness;
  float glint=step(0.993,rnd)*diffuse*(0.5+0.5*sin(t*2.0+rnd*47.0));
  tint+=LARAVEL_TIP*glint*fog*(0.55+sweep*2.0);

  vec2 ptr=vec2((uPointer.x-0.5)*2.0,(0.5-uPointer.y)*2.0);
  vec2 toPtr=vec2((u-ptr.x)*aspect,sy-ptr.y);
  float excite=(1.0-smoothstep(0.0,0.55,length(toPtr)))*uMisc.y;
  vec2 push=normalize(toPtr+vec2(0.0001,0.0))*excite*(0.006+0.010*rnd);
  tint*=1.0+excite*1.3;

  float floatAmp=(0.0015+(1.0-vFr)*0.006)*(1.0+excite*2.5);
  vec2 drift=vec2(sin(t*(0.35+rnd*0.50)+rnd*40.0),cos(t*(0.28+rnd*0.45)+rnd*23.0)*1.4)*floatAmp;
  vec2 shockPush=vec2(toCentre.x/max(dist,0.02)/aspect,toCentre.y/max(dist,0.02))*shock*0.045;
  float sizePx=clamp(16.0/z,0.65,1.95)*(0.75+0.5*sampleKey(seed+27.0))
    *(1.0+glint*0.25+ripple*0.20+sweep*0.55+shock*0.75);

  vec2 offset=QUAD[corner];
  gl_Position=vec4(vec2(u,sy)+drift+push+shockPush+offset*sizePx*2.0/res,0.5,1.0);
  vLocal=offset;
  vTint=vec4(tint*(1.0-occluded)*clearing,1.0);
}`;
