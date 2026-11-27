"use strict";
// Shared visual meaning: red is reserved for removed areas, including hatching.
window.SVMAreaStyle = {
 removedColor:'#b3483c', defaultColor:'#6a852e',
 isRed(value) {
  if(!/^#[0-9a-f]{6}$/i.test(value))return false;
  const [r,g,b]=[1,3,5].map(i=>parseInt(value.slice(i,i+2),16)/255);
  const max=Math.max(r,g,b),min=Math.min(r,g,b),delta=max-min;
  if(delta<.08 || max===0 || delta/max<.2)return false;
  let hue=max===r?60*((g-b)/delta%6):max===g?60*((b-r)/delta+2):60*((r-g)/delta+4);
  hue=(hue+360)%360;return hue<25||hue>=340;
 },
 existingColor(value){return /^#[0-9a-f]{6}$/i.test(value)&&!this.isRed(value)?value:this.defaultColor;},
 color(status,value){return status==='removed'?this.removedColor:this.existingColor(value);},
 banner(){const n=document.createElement('div');n.className='tree-popup__event';n.textContent='Odstraněný porost';n.style.cssText='margin:8px 12px 14px 0;padding:10px;border:2px solid #9b3028;color:#7b201b;background:#fff0ed;font-weight:700';return n;}
};
