'use strict';
(async()=>{
 const $=id=>document.getElementById(id),el=(tag,text)=>{const e=document.createElement(tag);if(text!==undefined)e.textContent=text;return e;},p=new URLSearchParams(location.search).get('p')||'';let generation=0;
 async function api(action,object='',body){const response=await fetch('api.php?'+new URLSearchParams({p,action,object}),{method:body?'POST':'GET',headers:body?{'Content-Type':'application/json'}:{},body:body?JSON.stringify(body):undefined,cache:'no-store'});let d;try{d=await response.json();}catch{throw Error('Server neodpověděl. Zkuste to znovu.');}if(!response.ok)throw Error(d.error||'Mapa není dostupná.');return d;}
 const map=L.map('map',{maxZoom:22}).setView([49.8,15.4],7);const osm=L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:22,maxNativeZoom:19,referrerPolicy:'strict-origin-when-cross-origin',attribution:'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'}).addTo(map);
 const ortho=L.tileLayer.wms('https://ags.cuzk.gov.cz/arcgis1/services/ORTOFOTO/MapServer/WMSServer',{layers:'0',format:'image/jpeg',version:'1.3.0',maxZoom:22,attribution:'© ČÚZK'});
 const cadastre=L.tileLayer.wms('https://services.cuzk.gov.cz/wms/local-KM-wms.asp',{layers:'KN',format:'image/png',transparent:true,version:'1.3.0',maxZoom:22,attribution:'© ČÚZK'});
 const white=L.gridLayer();white.createTile=()=>{const tile=el('div');tile.style.background='#fff';return tile;};
 L.control.layers({'OpenStreetMap':osm,'Ortofoto ČÚZK':ortho,'Bílý podklad':white},{'Katastrální mapa ČÚZK':cadastre}).addTo(map);
 const warning=L.control({position:'bottomleft'});warning.onAdd=()=>{const box=el('div','Podklad se nenačetl. Vyberte jiný v ovladači vrstev.');box.className='tile-warning';return box;};let warned=false;
 for(const tiles of [osm,ortho,cadastre])tiles.on('tileerror',()=>{if(!warned){warning.addTo(map);warned=true;}});
 map.on('baselayerchange',()=>{if(warned){map.removeControl(warning);warned=false;}});let layer,treeClusters,areaLayer;
 const locationControl=L.control({position:'topright'});
 locationControl.onAdd=()=>{
  const box=el('div');box.className='leaflet-bar public-location-control';
  const button=$('deviceLocation');button.textContent='🎯';button.title='Moje poloha';button.setAttribute('aria-label','Moje poloha');
  box.append(button);L.DomEvent.disableClickPropagation(box);L.DomEvent.disableScrollPropagation(box);return box;
 };
 locationControl.addTo(map);
 const fitControl=L.control({position:'topright'});
 fitControl.onAdd=()=>{
  const box=el('div');box.className='leaflet-bar public-location-control';
  const button=el('button','⛶');button.id='showAll';button.type='button';button.title='Zobrazit vše';button.setAttribute('aria-label','Zobrazit vše');button.disabled=true;
  button.onclick=()=>{if(layer&&layer.getLayers().length)map.fitBounds(layer.getBounds(),{padding:[20,20],maxZoom:18});};
  box.append(button);L.DomEvent.disableClickPropagation(box);L.DomEvent.disableScrollPropagation(box);return box;
 };
 fitControl.addTo(map);
 function locationButtonState(enabled){
  const button=$('deviceLocation'),label=enabled?'Vypnout polohu':'Moje poloha';
  button.setAttribute('aria-pressed',String(enabled));button.setAttribute('aria-label',label);button.title=label;
 }
 // Device location stays in this browser; never included in API requests.
 let locationWatch=null,locationEpoch=0,locationDot=null,accuracyCircle=null,locationEnabled=false,firstLocation=true;
 function stopLocation(message='Poloha je vypnutá.'){
  locationEnabled=false;locationEpoch++;if(locationWatch!==null)navigator.geolocation.clearWatch(locationWatch);locationWatch=null;
  if(locationDot)map.removeLayer(locationDot);if(accuracyCircle)map.removeLayer(accuracyCircle);locationDot=accuracyCircle=null;
  locationButtonState(false);$('locationStatus').textContent=message;
 }
 $('deviceLocation').onclick=()=>{
  if(locationEnabled){stopLocation();return;}
  if(!navigator.geolocation){$('locationStatus').textContent='Tento prohlížeč nepodporuje polohu zařízení.';return;}
  if(!window.isSecureContext){$('locationStatus').textContent='Poloha zařízení vyžaduje zabezpečené připojení HTTPS.';return;}
  locationEnabled=true;firstLocation=true;const epoch=++locationEpoch;locationButtonState(true);$('locationStatus').textContent='Zjišťuji polohu…';
  try{locationWatch=navigator.geolocation.watchPosition(position=>{
   if(!locationEnabled||epoch!==locationEpoch)return;
   const {latitude,longitude,accuracy}=position.coords;if(!Number.isFinite(latitude)||!Number.isFinite(longitude)||!Number.isFinite(accuracy)||accuracy<0)return;
   const point=[latitude,longitude];
   if(!locationDot){accuracyCircle=L.circle(point,{radius:accuracy,color:'#1976d2',weight:1,fillOpacity:.12,interactive:false}).addTo(map);locationDot=L.circleMarker(point,{radius:7,color:'#fff',weight:3,fillColor:'#1976d2',fillOpacity:1,interactive:false}).addTo(map);}
   else{locationDot.setLatLng(point);accuracyCircle.setLatLng(point).setRadius(accuracy);}
   if(firstLocation){map.setView(point,Math.max(map.getZoom(),17));firstLocation=false;}
   $('locationStatus').textContent='Přesnost polohy přibližně '+Math.round(accuracy)+' m.';
  },error=>{if(!locationEnabled||epoch!==locationEpoch)return;stopLocation(error.code===1?'Přístup k poloze byl zamítnut. Povolte jej v nastavení prohlížeče.':error.code===3?'Zjištění polohy trvalo příliš dlouho. Zkuste ji zapnout znovu.':'Polohu se nepodařilo zjistit. Zkuste ji zapnout znovu.');},{enableHighAccuracy:true,maximumAge:10000,timeout:20000});}
  catch{stopLocation('Polohu se nepodařilo zapnout.');}
 };
 window.addEventListener('pagehide',()=>stopLocation());
 $('close').onclick=()=>{generation++;$('detail').hidden=true;map.invalidateSize();};
 async function open(feature){const seq=++generation;$('detail').hidden=false;$('card').textContent='Načítám…';map.invalidateSize();try{const d=await api('detail',feature.properties.token);if(seq!==generation)return;const card=$('card');card.replaceChildren(el('h2',(d.kind==='trees'?'Strom ':'Plocha ')+d.code));const dl=el('dl');for(const v of d.values)dl.append(el('dt',v.label),el('dd',v.value));card.append(dl);
  for(const photo of d.photos){const figure=el('figure'),a=el('a'),img=el('img');a.href=photo.url;a.target='_blank';a.rel='noopener';img.src=photo.url;img.alt='Fotografie objektu '+d.code;img.loading='lazy';a.append(img);figure.append(a);if(photo.note)figure.append(el('figcaption',photo.note));card.append(figure);}
  if(d.reports){const button=el('button','Poslat podnět');card.append(button);button.onclick=async()=>{button.disabled=true;try{const c=await api('challenge',feature.properties.token);if(seq!==generation)return;const form=el('form'),message=el('textarea'),contact=el('input'),trap=el('input'),result=el('p'),submit=el('button','Odeslat podnět');message.required=true;message.minLength=10;message.maxLength=2000;message.rows=5;contact.type='email';contact.maxLength=200;trap.tabIndex=-1;trap.autocomplete='off';trap.className='trap';trap.setAttribute('aria-hidden','true');for(const [label,input] of [['Váš podnět',message],['Kontaktní e-mail (nepovinný)',contact]]){const l=el('label',label);l.append(input);form.append(l);}form.append(trap,el('p','Podnět a případný kontakt uvidí pouze správci projektu. Nezveřejní se na mapě. Uvádějte jen údaje potřebné k vyřízení.'),submit,result);card.append(form);message.focus();form.onsubmit=async e=>{e.preventDefault();submit.disabled=true;try{await api('report',feature.properties.token,{token:c.token,message:message.value,contact:contact.value,website:trap.value});form.replaceChildren(el('p','Děkujeme. Podnět byl uložen a správci jej uvidí v aplikaci.'));}catch(e){result.textContent=e.message;submit.disabled=false;}};}catch(e){button.disabled=false;$('status').textContent=e.message;}};}
 }catch(e){if(seq===generation)$('card').textContent=e.message;}}
 try{
  const data=await api('map');$('title').textContent=data.name;document.title=data.name+' – veřejná mapa';
  const value=(f,key)=>key==='@code'?f.properties.code:key==='@kind'?(f.properties.kind==='trees'?'Strom':'Plocha'):(f.properties.values||[]).find(v=>v.label===key)?.value??'';
  const fields=['@code','@kind',...new Set(Object.values(data.fields).flatMap(c=>Object.values(c).map(m=>m.label)))];
  const label=k=>k==='@code'?'Číslo objektu':k==='@kind'?'Typ objektu':k;
  for(const id of ['colorField'])for(const k of fields){const option=el('option',label(k));option.value=k;$(id).append(option);}
  const displayKey='svm-public-display:v1:'+p;
  const displayIds=['symbol','sizeMode','labels','colorField','areaColor','areaPattern','areaTransparency'];
  try{const saved=JSON.parse(localStorage.getItem(displayKey)||'{}');for(const id of displayIds){
   const control=$(id),v=saved[id];
   if(control.type==='checkbox'){if(typeof v==='boolean')control.checked=v;}
   else if(control.tagName==='SELECT'){if([...control.options].some(o=>o.value===v))control.value=v;}
   else if(id==='areaColor'&&/^#[0-9a-f]{6}$/i.test(v))control.value=v;
   else if(id==='areaTransparency'&&Number.isFinite(v)&&v>=0&&v<=100)control.value=String(v);
  }}catch{}
  function saveDisplay(){try{localStorage.setItem(displayKey,JSON.stringify(Object.fromEntries(displayIds.map(id=>[id,$(id).type==='checkbox'?$(id).checked:id==='areaTransparency'?Number($(id).value):$(id).value]))));}catch{}}
  for(const id of displayIds)$(id).addEventListener('input',saveDisplay);
  let page=0,sort='@code',ascending=true,visible=[],tableKind='trees';
  const filters={};
  for(const kind of ['trees','areas']){
   filters[kind]=SVMFilters.create(kind,data.fields[kind],{public:true,storage_scope:'public:'+p});
   filters[kind].mount($(kind==='trees'?'treeFilters':'areaFilters'));
  }
  const active=()=>$('search').value.trim()||Object.entries(filters).some(([kind,c])=>SVMFilters.isActive(c.state,kind));
  function filterIndicator(){const enabled=!!active();$('filterSummary').classList.toggle('has-active-filters',enabled);if(!enabled)SVMFilters.notice(false);}
  function matches(f){
   if(!$(f.properties.kind).checked)return false;
   const row=f.properties.filter_values||{},c=filters[f.properties.kind];
   return c.matches(row)&&SVMFilters.matches(row,{...SVMFilters.defaults(),query:$('search').value},c.fields,f.properties.kind);
  }
  // Shared tree-label renderer and the same zoom thresholds as the main map.
  TreeApp.map=map;TreeApp.installTreeLabelStyles();
  function refreshLabels(){
   TreeApp.showTreeLabels=$('labels').checked;TreeApp.refreshTreeLabels();
   areaLayer?.eachLayer(l=>{
    if(l.getTooltip())l.unbindTooltip();
    const code=String(l.feature.properties.code||'');
    if($('labels').checked&&map.getZoom()>=15&&code)l.bindTooltip(el('span',(/^p/i.test(code)?'':'p')+code),{permanent:true,direction:'center',className:'public-area-label',interactive:false});
   });
  }
  map.on('zoomend',refreshLabels);
  const areaRenderer=L.svg();
  function styleAreas(){
   $('areaTransparencyValue').textContent=$('areaTransparency').value+' %';
   const pattern=$('areaPattern').value,opacity=1-Number($('areaTransparency').value)/100;
   const container=areaRenderer._container;
   container?.querySelector('defs[data-public-patterns]')?.remove();
   const svg=(tag,attrs)=>{const n=document.createElementNS('http://www.w3.org/2000/svg',tag);for(const [k,v]of Object.entries(attrs))n.setAttribute(k,v);return n;};
   const defs=svg('defs',{'data-public-patterns':''});if(container)container.prepend(defs);
   let index=0;
   areaLayer?.eachLayer(l=>{
    const fill=$('colorField').value?color(l.feature):$('areaColor').value;
    l.setStyle({color:fill,fillColor:fill,fillOpacity:opacity});
    if(pattern!=='solid'&&container&&l._path){
     const id='public-area-pattern-'+index++,pat=svg('pattern',{id,patternUnits:'userSpaceOnUse',width:10,height:10});
     pat.append(svg('path',{d:pattern==='cross'?'M-2,2 L2,-2 M0,10 L10,0 M8,12 L12,8 M0,0 L10,10':'M-2,2 L2,-2 M0,10 L10,0 M8,12 L12,8',stroke:fill,'stroke-width':2}));
     defs.append(pat);l._path.setAttribute('fill','url(#'+id+')');
    }
   });
  }
  const mainColorFields={"Vitalita": "vitality", "Zdravotní stav": "tree_health", "Statická stabilita": "stability", "Sadovnická hodnota": "landscape_value", "Perspektiva": "longevity", "Další kontrola": "next_insp_at"};
  const palette=['#26733f','#2166ac','#b35806','#762a83','#b2182b','#00877a','#826b00','#bb4980','#555555'];
  let colors=new Map();
  function color(f){return $('colorField').value?colors.get(value(f,$('colorField').value))||'#777777':'#28613d';}
  function selectTable(kind,focus=false){
   tableKind=kind;page=0;sort='@code';ascending=true;
   for(const [k,id] of [['trees','tableTrees'],['areas','tableAreas']]){
    const selected=k===kind;$(id).setAttribute('aria-selected',String(selected));$(id).tabIndex=selected?0:-1;
    if(selected){$('tableContent').setAttribute('aria-labelledby',id);if(focus)$(id).focus();}
   }
   drawTable();
  }
  for(const [kind,id] of [['trees','tableTrees'],['areas','tableAreas']]){
   $(id).onclick=()=>selectTable(kind);
   $(id).onkeydown=e=>{if(['ArrowLeft','ArrowRight','Home','End'].includes(e.key)){
    e.preventDefault();selectTable(e.key==='Home'?'trees':e.key==='End'?'areas':tableKind==='trees'?'areas':'trees',true);
   }};
  }
  function drawTable(){
   const tableFields=['@code',...new Set(Object.entries(data.fields[tableKind]).filter(([k])=>!['usr_id','code'].includes(k)).map(([,m])=>m.label))];
   const tableRows=visible.filter(f=>f.properties.kind===tableKind);
   $('tableHead').replaceChildren();$('tableBody').replaceChildren();const head=el('tr');
   for(const k of tableFields){const th=el('th'),button=el('button',label(k)+(sort===k?(ascending?' ↑':' ↓'):''));button.type='button';button.onclick=()=>{ascending=sort===k?!ascending:true;sort=k;page=0;drawTable();};th.setAttribute('aria-sort',sort===k?(ascending?'ascending':'descending'):'none');th.append(button);head.append(th);}$('tableHead').append(head);
   const sorted=[...tableRows].sort((a,b)=>String(value(a,sort)).localeCompare(String(value(b,sort)),'cs',{numeric:true})*(ascending?1:-1));page=Math.max(0,Math.min(page,Math.ceil(sorted.length/50)-1));
   for(const f of sorted.slice(page*50,page*50+50)){const tr=el('tr');for(const k of tableFields){const td=el('td');if(k==='@code'){const button=el('button',value(f,k));button.type='button';button.title='Zobrazit objekt na mapě';button.onclick=()=>{map.fitBounds(L.geoJSON(f).getBounds(),{maxZoom:19,padding:[25,25]});open(f);};td.append(button);}else td.textContent=value(f,k);tr.append(td);}$('tableBody').append(tr);}
   $('page').textContent=(sorted.length?page+1:0)+' / '+Math.ceil(sorted.length/50)+' · '+sorted.length+(tableKind==='trees'?' stromů':' ploch');$('prev').disabled=page===0;$('next').disabled=(page+1)*50>=sorted.length;
  }
  function render(){
   if(treeClusters)map.removeLayer(treeClusters);if(areaLayer)map.removeLayer(areaLayer);visible=data.features.filter(matches);const key=$('colorField').value;colors=new Map();$('legend').replaceChildren();
   if(key){const categories=[...new Set(data.features.map(f=>value(f,key)))].sort((a,b)=>a.localeCompare(b,'cs',{numeric:true}));categories.forEach((v,i)=>{const field=mainColorFields[key],sample=field&&data.features.find(f=>f.properties.kind==='trees'&&value(f,key)===v&&Object.hasOwn(f.properties.marker||{},field));colors.set(v,sample?TreeApp.getTreeMarkerColor(sample.properties.marker,field==='next_insp_at'?'next_inspection':field):v===''?'#777777':palette[i%palette.length]);});for(const v of categories){const item=el('span'),swatch=el('i');swatch.style.backgroundColor=colors.get(v);item.append(swatch,document.createTextNode(v||'Nevyplněno'));$('legend').append(item);}}
   TreeApp.markersById={};TreeApp.treesById={};
   layer=L.geoJSON(visible,{renderer:areaRenderer,style:()=>({color:$('areaColor').value,weight:2,fillOpacity:.25}),pointToLayer:(f,ll)=>{
    const tree=f.properties.marker||{},size=TreeApp.getMarkerDimension(tree,$('sizeMode').value),background=$('colorField').value?color(f):$('symbol').value==='transparent'?'transparent':'#ffffff';
    const marker=L.marker(ll,{icon:TreeApp.createTreeMarkerIcon(tree,size,background)});marker._treeMarkerSize=size;
    TreeApp.markersById[f.properties.token]=marker;
    // Leaflet treats tooltip strings as HTML; encode public identifiers as text.
    const text=el('span',String(f.properties.code));TreeApp.treesById[f.properties.token]={usr_id:text.innerHTML};return marker;
   },onEachFeature:(f,l)=>{l.on('click',()=>open(f));}});
   treeClusters=L.markerClusterGroup({showCoverageOnHover:false,zoomToBoundsOnClick:true,spiderfyOnMaxZoom:true,disableClusteringAtZoom:19,maxClusterRadius:40,removeOutsideVisibleBounds:true});
   areaLayer=L.featureGroup();
   layer.eachLayer(l=>{if(l.feature.properties.kind==='trees')treeClusters.addLayer(l);else areaLayer.addLayer(l);});
   treeClusters.addTo(map);areaLayer.addTo(map);refreshLabels();styleAreas();
   $('showAll').disabled=layer.getLayers().length===0;
   $('status').textContent='Zobrazeno '+visible.length+' z '+data.features.length+' objektů.';filterIndicator();drawTable();
  }
  for(const id of ['search','trees','areas','symbol','sizeMode','labels','colorField'])$(id).addEventListener('input',()=>{page=0;render();});
  $('resetFilter').onclick=()=>{$('search').value='';for(const c of Object.values(filters))c.set(SVMFilters.defaults());$('trees').checked=$('areas').checked=true;page=0;render();};
  for(const c of Object.values(filters))c.subscribe(()=>{page=0;render();});
  for(const id of ['areaColor','areaPattern','areaTransparency'])$(id).addEventListener('input',styleAreas);
  $('prev').onclick=()=>{page--;drawTable();};$('next').onclick=()=>{page++;drawTable();};
  $('toggleTable').onclick=()=>{const show=$('tablePanel').hidden;$('tablePanel').hidden=!show;$('toggleTable').setAttribute('aria-expanded',String(show));$('toggleTable').textContent=show?'Skrýt tabulku':'Tabulka';map.invalidateSize();SVMFilters.notice(active());};
  $('controls').addEventListener('toggle',()=>map.invalidateSize());render();SVMFilters.notice(active());window.addEventListener('pageshow',e=>{if(e.persisted)SVMFilters.notice(active());});if(layer.getLayers().length)map.fitBounds(layer.getBounds(),{padding:[20,20],maxZoom:18});
 }catch(e){$('status').textContent=e.message;}
})();



