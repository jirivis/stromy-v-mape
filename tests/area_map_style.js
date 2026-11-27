'use strict';
const {JSDOM}=require('jsdom'),fs=require('fs'),assert=require('node:assert/strict');
(async()=>{
 const dom=new JSDOM('<div id="map"><div class="tree-marker-color-control__content"></div></div>',{url:'https://example.test/',runScripts:'outside-only',pretendToBeVisual:true}),w=dom.window;
 try {
 w.SVGSVGElement.prototype.createSVGRect=()=>({});
 w.eval(fs.readFileSync(require.resolve('leaflet/dist/leaflet.js'),'utf8'));
 const map=w.L.map('map').setView([50,15],17);
 w.TreeApp={initMap(){},map,projectAreaFilters:{matches:()=>true,subscribe(){}}};
 const features=['existing','removed'].map((status,i)=>({type:'Feature',geometry:{type:'Polygon',coordinates:[[[15+i*.01,50],[15.005+i*.01,50],[15+i*.01,50.005],[15+i*.01,50]]]},properties:{id:i+1,code:String(i+1),area_m2:10,attributes:{status,species:[]}}}));
 w.fetch=async()=>({ok:true,json:async()=>({project_id:1,features})});
 w.localStorage.setItem('svm-area-style:1',JSON.stringify({color:'#ff0000',pattern:'cross',transparency:75}));
 w.eval(fs.readFileSync('map/public/js/area_style.js','utf8'));
 w.eval(fs.readFileSync('map/public/js/areas_layer.js','utf8'));w.TreeApp.initMap();
 await new Promise(r=>setTimeout(r,60));
 const styles=[];map.eachLayer(l=>{if(l.feature&&l.options.interactive===false)styles.push(l);});
 assert.equal(styles.length,2);
 assert.equal(styles[0].options.color,'#6a852e');assert.equal(styles[0].options.fillColor,'#6a852e');
 assert.equal(styles[1].options.color,'#b3483c');assert.equal(styles[1].options.fillColor,'#b3483c');
 assert.match(styles[1]._path.getAttribute('fill'),/-removed\)/);
 assert.equal(w.document.querySelector('pattern[id$="-removed"] path').getAttribute('stroke'),'#b3483c');
 const popups=w.TreeApp.areaLayers.getLayers().map(l=>l.getPopup().getContent());
 assert.equal(popups[0].querySelector('.tree-popup__event'),null);
 assert.equal(popups[1].firstChild.textContent,'Odstraněný porost');
 const color=w.document.querySelector('[aria-label="Vlastní barva ploch"]');
 color.value='#d34b48';color.dispatchEvent(new w.Event('input'));assert.equal(color.value,'#6a852e');
 for(const red of ['#ff0000','#d34b48','#b3483c','#ffbbbb'])assert.equal(w.SVMAreaStyle.isRed(red),true);
 for(const color of ['#6a852e','#2979c9','#8658b8','#e49b20','#737373'])assert.equal(w.SVMAreaStyle.existingColor(color),color);
 map.remove();console.log('PASS removed area fill/outline/hatching, popup banner, reserved red and saved legacy color');
 }finally{w.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
