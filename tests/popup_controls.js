'use strict';
const {JSDOM}=require('jsdom'),fs=require('fs'),assert=require('node:assert/strict');
(async()=>{
 const dom=new JSDOM('<div id="map"></div>',{runScripts:'outside-only',pretendToBeVisual:true}),w=dom.window;
 try {
 w.eval(fs.readFileSync(require.resolve('leaflet/dist/leaflet.js'),'utf8'));
 w.eval(fs.readFileSync('map/public/js/popup_controls.js','utf8'));
 const map=w.L.map('map').setView([50,15],15);
 const control=map.zoomControl.getContainer();
 control.style.setProperty('visibility','visible','important');
 control.getBoundingClientRect=()=>({left:10,right:60,top:10,bottom:90,width:50,height:80});
 const popup=w.L.popup({autoPan:false}).setLatLng([50,15]).setContent('Test').openOn(map);
 let left=20;
 popup.getElement().getBoundingClientRect=()=>({left,right:left+200,top:20,bottom:160,width:200,height:140});
 const tick=()=>new Promise(r=>setTimeout(r,35));
 await tick();assert.equal(control.style.visibility,'hidden','overlapping control hidden');
 left=100;map.fire('move');await tick();assert.equal(control.style.visibility,'visible','nonoverlapping control restored');
 left=20;map.fire('resize');await tick();assert.equal(control.style.visibility,'hidden','resize recalculates overlap');
 map.closePopup();await tick();assert.equal(control.style.visibility,'visible','close restores control');
 assert.equal(control.style.getPropertyPriority('visibility'),'important','original priority preserved');
 map.remove();console.log('PASS popup controls: overlap, movement, resize, close, original styles');
 } finally {dom.window.close();}
})().catch(e=>{console.error(e);process.exit(1);});
