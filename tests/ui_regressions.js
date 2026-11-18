'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
for(const file of ['map/public/js/popup.js','map/public/tree_form/scripts.php']) {
 const source=fs.readFileSync(file,'utf8'),start=source.indexOf('function protectedTreePhotoUrl('),end=source.indexOf('\n}',start)+2;
 const ctx={URL,location:{href:'https://example.test/map/public/tree_new.php?id=1',origin:'https://example.test'}};
 vm.createContext(ctx);vm.runInContext(source.slice(start,end),ctx);
 const result=new URL(ctx.protectedTreePhotoUrl('uploads/a%20b.jpg?token=share'));
 assert.equal(result.pathname,'/map/public/photo.php');assert.equal(result.searchParams.get('file'),'a b.jpg');assert.equal(result.searchParams.get('token'),'share');
 assert.equal(ctx.protectedTreePhotoUrl('https://other.test/uploads/a.jpg'),'https://other.test/uploads/a.jpg');
 assert.equal(ctx.protectedTreePhotoUrl('photo.php?photo=12&token=abc'),'photo.php?photo=12&token=abc');
}
const source=fs.readFileSync('map/public/js/markers.js','utf8');const a=source.indexOf('TreeApp.getMarkerDimension'),b=source.indexOf('// Styl popisku',a);
const ctx={TreeApp:{}};vm.createContext(ctx);vm.runInContext(source.slice(a,b),ctx);
for(const mode of ['none','girth','dbh','spread','height'])assert.equal(ctx.TreeApp.getMarkerDimension({},mode),20);
assert.equal(ctx.TreeApp.getMarkerDimension({spread_m:20},'spread'),80);
console.log('PASS image gateway URLs and readable minimum marker dimensions');

ctx.window={matchMedia:()=>({matches:true})};
for(const mode of ['none','girth','dbh','spread','height'])assert.equal(ctx.TreeApp.getMarkerDimension({},mode),20);
console.log('PASS touch devices retain original minimum dimensions');

assert.match(source,/font: 700 13px\/1\.2/);
assert.doesNotMatch(source,/font: 700 16px/);

// Project extent must work for areas alone, mixed objects, and empty filters.
{
 const selection=fs.readFileSync('map/public/js/selection.js','utf8');
 const start=selection.indexOf('TreeApp.fitMapToFilteredTrees =');
 const finish=selection.indexOf('\n};',start)+3;
 const makeBounds=()=>({points:[],extend(p){this.points.push(...(p.points||[p]));return this;},isValid(){return this.points.length>0;}});
 let rows=[],calls=[],areas=makeBounds();
 const app={map:{hasLayer:()=>true,fitBounds:b=>calls.push(b.points)},markersById:{1:{getLatLng:()=>[50,14]}},areaLayers:{getBounds:()=>areas}};
 const context={TreeApp:app,L:{latLngBounds:makeBounds}};vm.createContext(context);vm.runInContext(selection.slice(start,finish),context);
 areas.extend([49,15]);app.fitMapToFilteredTrees();assert.deepEqual(calls.pop(),[[49,15]]);
 app.dataTable={rows:()=>({nodes:()=>({toArray:()=>rows})})};rows=[{dataset:{treeId:1}}];
 app.fitMapToFilteredTrees();assert.deepEqual(calls.pop(),[[50,14],[49,15]]);
 areas=makeBounds();app.fitMapToFilteredTrees();assert.deepEqual(calls.pop(),[[50,14]]);
 rows=[];app.fitMapToFilteredTrees();assert.equal(calls.length,0);
 console.log('PASS project extent: areas only, mixed, trees only and empty');
}

// Both autocomplete entry points use this callback, even for hidden controls.
{
 const script=fs.readFileSync('map/public/tree_form/scripts.php','utf8');
 const start=script.indexOf('function selectSpecies(item) {'),end=script.indexOf('\ncreateAutocomplete(document.querySelector("#species")',start);
 const inputs=Object.fromEntries(['species','scientific_author','czech','angio_gymno'].map(k=>['#'+k,{value:'old',hidden:true}]));
 let flag;const form={querySelector:()=>flag,append:n=>flag=n};
 const context={document:{querySelector:s=>s.startsWith('form[')?form:inputs[s],createElement:()=>({})}};
 vm.createContext(context);vm.runInContext(script.slice(start,end),context);
 context.selectSpecies({scientific_name:'Pinus sylvestris',author:'L.',czech_name:'borovice lesní',family:'Pinaceae'});
 assert.equal(inputs['#scientific_author'].value,'L.');assert.equal(inputs['#czech'].value,'borovice lesní');assert.equal(inputs['#angio_gymno'].value,'gymnospermae');assert.equal(flag.value,'1');
 context.selectSpecies({scientific_name:'Unknown'});assert.equal(inputs['#scientific_author'].value,'');assert.equal(inputs['#angio_gymno'].value,'');
 console.log('PASS autocomplete updates hidden taxonomy and clears stale values');
}

