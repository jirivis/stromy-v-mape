'use strict';
const {JSDOM}=require('jsdom'),fs=require('fs'),vm=require('vm'),assert=require('node:assert/strict');
const dom=new JSDOM('<div id="map"></div>',{url:'https://example.test',runScripts:'outside-only',pretendToBeVisual:true}),w=dom.window;
try {
 w.SVGSVGElement.prototype.createSVGRect=()=>({});
 w.HTMLDialogElement.prototype.showModal=function(){this.open=true;};
 w.HTMLDialogElement.prototype.close=function(){this.open=false;};
 w.eval(fs.readFileSync(require.resolve('leaflet/dist/leaflet.js'),'utf8'));
 const map=w.L.map('map').setView([50,15],17),marker=w.L.marker([50,15],{icon:w.L.divIcon({html:'<span>tree</span>'})}).addTo(map).bindPopup('ordinary popup');
 const area=w.L.geoJSON({type:'Feature',properties:{id:2,code:'2',attributes:{}},geometry:{type:'Polygon',coordinates:[[[14.99,49.99],[15.01,49.99],[15.01,50.01],[14.99,50.01],[14.99,49.99]]]}});
 w.TreeApp={initMap(){},map,markersById:{1:marker},treesById:{1:{usr_id:1}},areaLayers:area};
 w.TreeAppUser={projectRole:'editor',userId:1,projectId:1};w.localStorage.setItem('svm-edit-mode:1:1','1');
 let destination='';
 vm.runInNewContext(fs.readFileSync('map/public/js/edit_mode.js','utf8'),{TreeApp:w.TreeApp,window:w,document:w.document,Element:w.Element,L:w.L,localStorage:w.localStorage,location:{set href(v){destination=v;}}});
 w.TreeApp.initMap();
 const click=el=>el.dispatchEvent(new w.MouseEvent('click',{bubbles:true,cancelable:true,clientX:0,clientY:0}));
 assert.equal(marker.getElement().getAttribute('role'),'button');
 click(marker.getElement().firstChild);assert.equal(destination,'/map/public/tree_new.php?id=1');assert.equal(map._popup,undefined);
 destination='';area.addTo(map);click(marker.getElement().firstChild);
 const dialog=w.document.querySelector('dialog');assert.equal(dialog.open,true);assert.equal(destination,'');assert.equal(dialog.querySelectorAll('div button').length,2);assert.ok(!map._popup);
 click(dialog.querySelector('div button:last-child'));assert.equal(destination,'/map/public/areas/?id=2');assert.equal(dialog.open,false);
 marker.remove();destination='';click(area.getLayers()[0].getElement());assert.equal(destination,'/map/public/areas/?id=2','single area opens editor');marker.addTo(map);
 area.remove();const second=w.L.marker([50,15],{icon:w.L.divIcon()}).addTo(map);w.TreeApp.markersById[3]=second;
 click(marker.getElement());assert.equal(dialog.querySelectorAll('div button').length,2);dialog.close();
 delete w.TreeApp.markersById[3];second.remove();
 click(w.document.querySelector('.svm-edit-toggle'));destination='';click(marker.getElement());assert.equal(destination,'');assert.ok(map._popup,'normal mode keeps popup');
 map.remove();console.log('PASS edit mode: nested marker tap, single tree, tree/area overlap, overlapping trees, ordinary popup');
} finally {w.close();}
