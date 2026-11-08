'use strict';
// Shared by the renderer and collision tests. Coordinates are output pixels.
window.OverviewLayout = {
 placeLabels(labels, obstacles, width, height, measure) {
  const cells = new Map(), step = 64;
  const keys = r => {const out=[];for(let x=Math.floor(r.x/step);x<=Math.floor((r.x+r.w)/step);x++)for(let y=Math.floor(r.y/step);y<=Math.floor((r.y+r.h)/step);y++)out.push(x+':'+y);return out;};
  const add=r=>{for(const k of keys(r)){if(!cells.has(k))cells.set(k,[]);cells.get(k).push(r);}};
  const overlaps=(a,b)=>a.x<b.x+b.w&&a.x+a.w>b.x&&a.y<b.y+b.h&&a.y+a.h>b.y;
  const free=r=>r.x>=8&&r.y>=8&&r.x+r.w<=width-8&&r.y+r.h<=height-8&&!keys(r).some(k=>(cells.get(k)||[]).some(o=>overlaps(r,o)));
  obstacles.forEach(add);const placed=[],omitted=[];
  for(const label of labels){
   const w=measure(String(label.text),label.size)+6,h=label.size+6;let found=null;
   // Keep the baseline beside the point before trying positions farther away.
   for(const gap of [0,3,6,12,20,32,48]){
    const r=gap;
    const candidates=[...(gap===0?[[label.x-w/2,label.y-h/2]]:[]),[label.x+r,label.y-h+4],[label.x+r,label.y-h/2],[label.x-r-w,label.y-h+4],[label.x-r-w,label.y-h/2],[label.x-w/2,label.y-r-h],[label.x-w/2,label.y+r],[label.x+r,label.y+r],[label.x-r-w,label.y+r]];
    for(const [x,y] of candidates){const box={x,y,w,h};if(free(box)){found={...label,box,leader:gap>=12};break;}}
    if(found)break;
   }
   if(found){placed.push(found);add(found.box);}else omitted.push(label.text);
  }
  return {placed,omitted};
 },
 wrap(value,maxWidth,measure){
  const lines=[];let line='';
  for(const word of String(value).trim().split(/\s+/)){
   if(!word)continue;
   if(line&&measure(line+' '+word)>maxWidth){lines.push(line);line='';}
   for(const ch of (line?' ':'')+word){if(line&&measure(line+ch)>maxWidth){lines.push(line);line='';}line+=ch;}
  }
  if(line)lines.push(line);return lines;
 }
};
