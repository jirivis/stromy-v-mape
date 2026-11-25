'use strict';
const {JSDOM}=require('jsdom'),fs=require('fs'),assert=require('node:assert/strict');
const root='map/public/',read=p=>fs.readFileSync(root+p,'utf8');
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
(async()=>{
const dom=new JSDOM(read('areas/index.php'),{url:'https://example.test/map/public/areas/?id=1',runScripts:'outside-only',pretendToBeVisual:true}),w=dom.window,d=w.document;
try{
w.SVGSVGElement.prototype.createSVGRect=()=>({});w.HTMLElement.prototype.scrollIntoView=()=>{};
w.eval(fs.readFileSync(require.resolve('leaflet/dist/leaflet.js'),'utf8'));
w.eval(fs.readFileSync(require.resolve('leaflet-draw/dist/leaflet.draw.js'),'utf8'));
const feature={type:'Feature',geometry:{type:'Polygon',coordinates:[[[15,50],[15.001,50],[15,50.001],[15,50]]]},properties:{id:1,code:'1',version:1,attributes:{status:'existing',species:[{scientific_name:'Ribes alpinum',author:'L.',czech_name:'rybíz alpínský',share:100}]}}};
const other=JSON.parse(JSON.stringify(feature));other.properties.id=2;other.properties.code='2';
w.AreasBoot={project_id:4,can_edit:true,project_name:'Demo',csrf:'test',after_save:{create:'form',edit:'form'}};
let saved;
w.fetch=async(url,opts={})=>{let data;if(String(url).includes('kind=trees'))data={project_id:4,rows:[{geometry:{type:'Point',coordinates:[15,50]},values:{usr_id:3}}]};else if(String(url).includes('species_search'))data=[{scientific_name:'Mahonia aquifolium',author:'(Pursh) Nutt.',czech_name:'mahónie cesmínolistá'}];else if(String(url).includes('action=list'))data={project_id:4,features:[feature,other]};else if(String(url).includes('action=history'))data={project_id:4,rows:[]};else if(String(url).includes('action=save')){saved=JSON.parse(opts.body);feature.properties.attributes=saved.attributes;data={project_id:4,feature};}else data={project_id:4,feature,photos:[]};return {ok:true,json:async()=>data};};
for(const script of ['js/project_context_map.js','areas/boundary_editor.js','areas/areas.js'])w.eval(read(script));
await sleep(100);
assert.equal(d.getElementById('formTitle').textContent,'Upravit plochu 1');
assert.equal(d.querySelector('[data-author]').value,'L.');
assert.equal(d.querySelectorAll('.context-tree').length,1);
assert.ok(d.querySelector('[title="Zobrazit editovanou plochu"]'));
d.querySelector('[title="Zobrazit editovanou plochu"]').click();
assert.ok(d.getElementById('save').compareDocumentPosition(d.getElementById('areaPhotos'))&w.Node.DOCUMENT_POSITION_FOLLOWING);
assert.ok(d.getElementById('areaPhotos').compareDocumentPosition(d.getElementById('removedArea'))&w.Node.DOCUMENT_POSITION_FOLLOWING);
assert.equal(d.getElementById('areaPhotos').closest('form'),null);
const help=d.querySelector('.methodology-help');assert.equal(help.querySelector('iframe'),null);help.open=true;help.dispatchEvent(new w.Event('toggle'));assert.ok(help.querySelector('iframe').src.endsWith('#page=31'));
const row=d.querySelector('.species-row'),latin=row.querySelector('input');latin.value='Mahonia';latin.dispatchEvent(new w.Event('input',{bubbles:true}));assert.equal(row.querySelector('[data-author]').value,'');await sleep(260);row.querySelector('.suggest button').click();assert.equal(row.querySelector('[data-author]').value,'(Pursh) Nutt.');
d.getElementById('areaForm').dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await sleep(40);assert.equal(saved.id,1);assert.equal(saved.attributes.species[0].author,'(Pursh) Nutt.');assert.equal(saved.attributes.species[0].share,'100');
w.eval(read('project_tools/porost_report.js'));const fields={species:{label:'Druhy'},status:{label:'Stav'},area_m2:{label:'Výměra'}};const values={code:'1',status:'existing',area_m2:5,species:[{scientific_name:'Ribes alpinum',author:'L.',czech_name:'rybíz'}]};
const report=w.PorostReport.render(values,fields,'Test');assert.ok(report.querySelector('.porost-subtitle').textContent.includes('Ribes alpinum L.'));assert.ok(report.textContent.includes('Ribes alpinum L.'));assert.equal(report.textContent.includes('Existující'),false);assert.equal(report.querySelector('i').textContent,'Ribes alpinum');assert.ok(w.PorostReport.render({...values,status:'removed'},fields).textContent.includes('Odstraněný'));
// Project switch must fail before contextual data is drawn.
w.fetch=async()=>({ok:true,json:async()=>({project_id:999,rows:[]})});await assert.rejects(()=>w.SVMContextMap.data('/unused',4),/projekt/);
console.log('PASS area form: authors, context, focus control, photo order, help, save and report');
}finally{w.close();}
})().catch(e=>{console.error(e);process.exitCode=1});

// Real Leaflet: visual polygons above markers must not intercept their events.
(async()=>{
 const dom=new JSDOM('<div id="map"></div>',{url:'https://example.test/map/public/',runScripts:'outside-only',pretendToBeVisual:true}),w=dom.window;
 try{
 w.SVGSVGElement.prototype.createSVGRect=()=>({});w.eval(fs.readFileSync(require.resolve('leaflet/dist/leaflet.js'),'utf8'));
 const map=w.L.map('map').setView([50,15],18),feature={type:'Feature',geometry:{type:'Polygon',coordinates:[[[15,50],[15.001,50],[15,50.001],[15,50]]]},properties:{id:1,code:'1',area_m2:50,attributes:{status:'existing'}}};
 w.TreeApp={map,initMap(){},projectAreaFilters:{matches:()=>true,subscribe(){},mount(){}}};w.fetch=async()=>({ok:true,json:async()=>({features:[feature],project_id:4})});
 w.eval(read('js/areas_layer.js'));w.TreeApp.initMap();await sleep(40);
 const pane=map.getPane('areaVisuals');assert.equal(pane.style.zIndex,'625');assert.equal(pane.style.pointerEvents,'none');assert.equal(pane.querySelectorAll('g > path').length,1);assert.equal(pane.querySelector('.leaflet-interactive'),null);
 const hit=w.TreeApp.areaLayers.getLayers()[0];assert.equal(hit.options.fillOpacity,0);assert.equal(hit.options.opacity,0);assert.ok(hit.getPopup());
 map.removeLayer(w.TreeApp.areaLayers);assert.equal(pane.querySelectorAll('g > path').length,0);w.TreeApp.areaLayers.addTo(map);assert.equal(pane.querySelectorAll('g > path').length,1);
 console.log('PASS main map transparent area overlay, marker hit targets and layer toggle');map.remove();
 }finally{w.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
