const {JSDOM}=require('jsdom'),fs=require('fs'),assert=require('assert');
const dir=process.cwd()+'/map/public/community/';
const dom=new JSDOM(fs.readFileSync(dir+'index.php','utf8'),{url:'https://example.org/community/?p=abc',runScripts:'outside-only'}),w=dom.window,$=id=>w.document.getElementById(id);
let fitted=null,zoom=17,shown=[],clusters=[],polygons=[],events={};
const map={getZoom:()=>zoom,setView(){return this},invalidateSize(){},removeLayer(){},fitBounds(bounds){fitted=bounds},on(name,fn){events[name]=fn},removeControl(){}};
const tile=()=>({addTo(){return this},on(){return this}});
const control=options=>({addTo(){if(this.onAdd){const box=this.onAdd();box.dataset.position=options.position;$('map').append(box)}return this}});control.layers=tile;
const dot=()=>({addTo(){return this},setLatLng(){return this},setRadius(){return this}});
let locate,locateError,watches=0,clears=0;
Object.defineProperty(w,'isSecureContext',{value:true});
Object.defineProperty(w.navigator,'geolocation',{value:{watchPosition(ok,error){locate=ok;locateError=error;watches++;return 7},clearWatch(id){assert.equal(id,7);clears++}}});
const vector=()=>({_path:w.document.createElementNS('http://www.w3.org/2000/svg','path'),on(){},getTooltip(){return this.tip},unbindTooltip(){this.tip=null},bindTooltip(text,options){this.tip={text,options}},setStyle(style){this.style=style;this._path.setAttribute('fill',style.fillColor)}});
w.L={
 svg:()=>({_container:w.document.createElementNS('http://www.w3.org/2000/svg','svg')}),
 point:(x,y)=>({x,y}),
 markerClusterGroup:options=>{assert.equal(options.disableClusteringAtZoom,19);assert.equal(options.maxClusterRadius,40);clusters=[];return {addLayer(l){clusters.push(l)},addTo(){return this}}},
 featureGroup:()=>{polygons=[];return {addLayer(l){polygons.push(l)},eachLayer(fn){polygons.forEach(fn)},addTo(){return this}}},
 DomEvent:{disableClickPropagation(){},disableScrollPropagation(){}},circle:dot,map:()=>map,tileLayer:Object.assign(tile,{wms:tile}),gridLayer:tile,control,circleMarker:dot,marker:vector,divIcon:x=>x,
 geoJSON:(features,opts)=>{const list=Array.isArray(features)?features:[features];if(opts)shown=list;const layers=list.map(f=>{const l=opts&&f.geometry.type==='Point'?opts.pointToLayer(f,[]):vector();l.feature=f;opts?.onEachFeature(f,l);return l});return {eachLayer(fn){layers.forEach(fn)},getLayers:()=>layers,getBounds:()=>({features:list})}}
};
const data={name:'Test',fields:{trees:{usr_id:{label:'Číslo stromu'},height:{label:'Výška',type:'number'},species:{label:'Druh'},next_insp_at:{label:'Kontrola',type:'date'}},areas:{code:{label:'Číslo plochy'},height_max:{label:'Výška',type:'number'}}},features:[
 {type:'Feature',geometry:{type:'Point',coordinates:[15,50]},properties:{kind:'trees',code:'1',token:'a',filter_values:{usr_id:1,height:10,species:'Buk',next_insp_at:null},values:[{label:'Výška',value:'10'}],marker:{height:10}}},
 {type:'Feature',geometry:{type:'Polygon',coordinates:[]},properties:{kind:'areas',code:'P2',token:'b',filter_values:{code:'P2',height_max:20},values:[{label:'Výška',value:'20'}]}}
]};
let requests=0;w.fetch=async()=>{requests++;return {ok:true,json:async()=>data}};
w.TreeApp={};w.eval(fs.readFileSync(dir+'../js/markers.js','utf8'));w.eval(fs.readFileSync(dir+'../js/project_filters.js','utf8'));
// A stale filter on a formerly published field must disappear, including unavailable quick filters.
w.localStorage.setItem('svm-filters:v1:0:0:trees:public:abc',JSON.stringify({...w.SVMFilters.defaults(),rules:[{field:'note_tree',op:'contains',value:'secret'}],group:'gymnospermae',urgency:'u0'}));
w.eval(fs.readFileSync(dir+'community.js','utf8'));
setImmediate(()=>{try{
 const input=id=>$(id).dispatchEvent(new w.Event('input'));
 const c=w.SVMFilters.create('trees',{}, {public:true,storage_scope:'public:abc'});
 assert.equal(c.state.rules.length,0);assert.equal(c.state.group,'');assert.equal(c.state.urgency,'all');
 assert.equal($('treeFilters').textContent.includes('Naléhavost zásahu'),false);
 assert.equal($('treeFilters').querySelector('[aria-label="Příští kontrola"]')!==null,true);
 assert.equal(shown.length,2);assert.equal(clusters.length,1);assert.equal(polygons.length,1);
 assert.equal($('tableBody').children.length,1);assert.equal($('tableBody').textContent.includes('P2'),false);
 assert.equal($('tableHead').textContent.includes('Druh'),true);
 $('tableAreas').click();assert.equal($('tableBody').textContent.includes('P2'),true);assert.equal($('tableHead').textContent.includes('Druh'),false);
 $('toggleTable').click();$('tableAreas').dispatchEvent(new w.KeyboardEvent('keydown',{key:'ArrowLeft'}));assert.equal($('tableTrees').getAttribute('aria-selected'),'true');assert.equal(w.document.activeElement,$('tableTrees'));$('toggleTable').click();
 const originalTimeout=w.setTimeout;let delay,fade;
 w.setTimeout=(fn,ms)=>{delay=ms;fade=fn;return 123};
 w.SVMFilters.notice(true);assert.equal(delay,1500);assert.ok(w.document.getElementById('svm-filter-notice-style').textContent.includes('top:50%;left:50%;transform:translate(-50%,-50%)'));
 fade();assert.equal($('svm-active-filter-notice').style.opacity,'0');w.setTimeout=originalTimeout;w.SVMFilters.notice(false);

 assert.equal(clusters[0].tip.options.direction,'bottom');assert.equal(polygons[0].tip.options.direction,'center');
 zoom=14;events.zoomend();assert.equal(clusters[0].tip,null);assert.equal(polygons[0].tip,null);
 zoom=15;events.zoomend();assert.equal(clusters[0].tip,null);assert.ok(polygons[0].tip);
 zoom=17;events.zoomend();assert.ok(clusters[0].tip);
 $('labels').checked=false;input('labels');assert.equal(clusters[0].tip,null);assert.equal(polygons[0].tip,null);
 $('labels').checked=true;input('labels');
 $('showAll').click();assert.equal(fitted.features.length,2);
 const host=$('treeFilters'),button=text=>[...host.querySelectorAll('button')].find(b=>b.textContent===text);
 button('Přidat podmínku').click();let row=host.querySelector('.svm-filter-rule');row.children[0].value='height';row.children[1].value='gt';row.children[2].value='15';
 button('Použít podmínky').click();assert.equal(shown.length,1);assert.equal(clusters.length,0);assert.equal($('tableBody').children.length,0);$('tableAreas').click();assert.equal($('tableBody').children.length,1);assert.equal($('tableAreas').getAttribute('aria-selected'),'true');assert.equal($('tableContent').getAttribute('aria-labelledby'),'tableAreas');assert.equal($('filterSummary').classList.contains('has-active-filters'),true);
 c.set({...c.state,mode:'any',rules:[...c.state.rules,{field:'species',op:'eq',value:'Buk'}]});assert.equal(shown.length,2);
 c.set({...c.state,mode:'all'});assert.equal(shown.length,1);
 assert.equal(w.SVMFilters.create('trees',data.fields.trees,{public:true,storage_scope:'public:other'}).state.rules.length,0);
 assert.equal(w.SVMFilters.create('trees',data.fields.trees).state.rules.length,0);
 $('search').value='missing';input('search');assert.equal($('showAll').disabled,true);$('resetFilter').click();assert.equal(shown.length,2);
 $('areaColor').value='#123456';input('areaColor');assert.equal(polygons[0].style.fillColor,'#123456');
 $('areaTransparency').value='40';input('areaTransparency');assert.equal(polygons[0].style.fillOpacity,.6);
 $('areaPattern').value='cross';input('areaPattern');assert.ok(polygons[0]._path.getAttribute('fill').startsWith('url('));
 $('areaPattern').value='solid';input('areaPattern');assert.equal(polygons[0]._path.getAttribute('fill'),'#123456');
 $('colorField').value='Výška';input('colorField');assert.equal($('legend').children.length,2);
 $('toggleTable').click();assert.equal($('tablePanel').hidden,false);
 assert.equal($('deviceLocation').closest('.public-location-control').dataset.position,'topright');
 $('deviceLocation').click();assert.equal(watches,1);locate({coords:{latitude:50,longitude:15,accuracy:12}});assert.ok($('locationStatus').textContent.includes('12 m'));
 $('deviceLocation').click();assert.equal(clears,1);$('deviceLocation').click();locateError({code:1});assert.equal(clears,2);assert.equal(requests,1);
 console.log('PASS public shared filters, public-only field scope, all/any, table, cluster, zoom labels, area styles and device location');
}catch(e){console.error(e);process.exitCode=1}finally{w.close()}});

