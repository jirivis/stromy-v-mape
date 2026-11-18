'use strict';
(function(){
// Display-only prefix; keep legacy codes such as P1 and database values unchanged.
function areaMapLabel(code){const value=String(code??'').trim();return /^\d+$/.test(value)?'p'+value:value;}
const original=TreeApp.initMap;
TreeApp.initMap=function(...args){
 const result=original.apply(this,args),map=TreeApp.map;
 let features=[],state={color:'#6a852e',pattern:'solid',transparency:75},storageKey=null,tableBody=null,tableStatus=null;
 const valid=s=>s && /^#[0-9a-f]{6}$/i.test(s.color)&&['solid','diagonal','cross'].includes(s.pattern)&&Number.isFinite(s.transparency)&&s.transparency>=0&&s.transparency<=100;
 const el=(tag,text)=>{const n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;};
 const renderer=L.svg();
 const polygons=L.geoJSON(null,{renderer,style:f=>({color:f.properties.attributes.status==='removed'?'#b3483c':state.color,fillColor:state.color,fillOpacity:1-state.transparency/100,weight:2}),onEachFeature:(f,l)=>{
  const p=f.properties,a=p.attributes,box=el('article');box.className='tree-popup';const header=el('header');header.className='tree-popup__header';const heading=el('div');heading.className='tree-popup__heading';const number=el('div','Porost č. '+p.code);number.className='tree-popup__number';heading.append(number);if(a.name){const name=el('div',a.name);name.className='tree-popup__name';heading.append(name);}const actions=el('div');actions.className='tree-popup__actions';
  const user=window.TreeAppUser||{};if(['owner','admin','editor'].includes(user.projectRole)||Number(user.isSuperAdmin)===1){const edit=el('a','Upravit');edit.href='/map/public/areas/?id='+Number(p.id);edit.className='tree-popup__button';actions.append(edit);}const card=el('a','Karta');card.href='/map/public/project_tools/area_card.php?id='+Number(p.id);card.target='_blank';card.rel='noopener noreferrer';card.className='tree-popup__button';actions.append(card);header.append(heading,actions);box.append(header);
  const attrs=el('section');attrs.className='tree-popup__attributes';const entries=[['Druhové složení',(a.species||[]).map(s=>s.czech_name||s.scientific_name).join(', ')],['Výměra',Number(p.area_m2).toLocaleString('cs-CZ')+' m²'],['Stav',a.status==='removed'?'Odstraněný porost':'Existující porost'],['Datum měření',a.measured_at],['Další kontrola',a.next_insp_at]];for(const [label,value] of entries){if(value===undefined||value===null||value==='')continue;const row=el('div'),key=el('span',label),val=el('span',value);row.className='tree-popup__row';key.className='tree-popup__label';val.className='tree-popup__value';row.append(key,val);attrs.append(row);}box.append(attrs);
  let photoLoaded=false;l.on('popupopen',async()=>{if(photoLoaded)return;try{const res=await fetch('/map/public/areas/api.php?action=get&id='+Number(p.id),{credentials:'same-origin',cache:'no-store'});if(!res.ok)return;const d=await res.json();if(!d.photos?.length){photoLoaded=true;return;}const gallery=el('section'),link=el('a'),img=el('img');gallery.className='tree-popup__gallery';link.className='tree-popup__photo-link';link.href=card.href;link.target='_blank';link.rel='noopener';img.className='tree-popup__photo';img.src='/map/public/project_tools/card_photo.php?kind=areas&id='+Number(d.photos[0].id)+'&thumb=1';img.alt='Porost '+p.code;link.append(img);gallery.append(link);box.append(gallery);photoLoaded=true;}catch{}});
  l.bindPopup(box,TreeApp.treePopupOptions||{minWidth:280,maxWidth:380});l.bindTooltip(el('span',areaMapLabel(p.code)),{permanent:true,direction:'center'});l.on('add',()=>{applyStyle();updateLabels();});
 }}).addTo(map);
 function updateLabels(){polygons.eachLayer(l=>{const tip=l.getTooltip();if(tip){const n=tip.getElement();if(n)n.style.display=map.getZoom()>=15?'':'none';}});}
 map.on('zoomend',updateLabels);
 TreeApp.areaLayers=polygons;
 if(TreeApp.layersControl)TreeApp.layersControl.addOverlay(polygons,'Plochy porostů');
 const control=L.control({position:'topleft'});
 control.onAdd=()=>{const a=el('a','＋ plocha');a.className='leaflet-bar svm-add-area';a.href='/map/public/areas/?new=1';a.title='Přidat novou plochu';a.addEventListener('click',()=>{const c=map.getCenter();a.href='/map/public/areas/?new=1&lat='+c.lat+'&lng='+c.lng+'&zoom='+map.getZoom();});L.DomEvent.disableClickPropagation(a);return a;};
 const u=window.TreeAppUser||{};if(['owner','admin','editor'].includes(u.projectRole)||Number(u.isSuperAdmin)===1){control.addTo(map);const treeButton=map.getContainer().querySelector('.add-tree-btn');if(treeButton)treeButton.after(control.getContainer());}
 const svgNS='http://www.w3.org/2000/svg',patternId='svm-area-hatch-'+L.Util.stamp(map);
 function applyStyle(){
  const svg=renderer._container;
  if(svg){let defs=svg.querySelector('defs');if(!defs){defs=document.createElementNS(svgNS,'defs');svg.prepend(defs);}let pattern=svg.querySelector('#'+patternId);if(!pattern){pattern=document.createElementNS(svgNS,'pattern');pattern.id=patternId;pattern.setAttribute('patternUnits','userSpaceOnUse');pattern.setAttribute('width','10');pattern.setAttribute('height','10');defs.append(pattern);}pattern.replaceChildren();const path=document.createElementNS(svgNS,'path');path.setAttribute('d',state.pattern==='cross'?'M-1,1 L1,-1 M0,10 L10,0 M9,11 L11,9 M0,0 L10,10':'M-1,1 L1,-1 M0,10 L10,0 M9,11 L11,9');path.setAttribute('stroke',state.color);path.setAttribute('stroke-width','2');pattern.append(path);}
  polygons.eachLayer(l=>{l.setStyle({color:l.feature.properties.attributes.status==='removed'?'#b3483c':state.color,fillColor:state.color,fillOpacity:1-state.transparency/100});if(state.pattern!=='solid'&&l._path)l._path.setAttribute('fill','url(#'+patternId+')');});
 }
 const areaFilters=TreeApp.projectAreaFilters;
 function filtered(){return features.filter(f=>areaFilters.matches({...f.properties.attributes,code:f.properties.code,area_m2:f.properties.area_m2}));}
 function applyFilter(){polygons.clearLayers();polygons.addData({type:'FeatureCollection',features:filtered()});applyStyle();updateLabels();renderRows();}
 areaFilters.subscribe(applyFilter);
 function filterFields(){const box=el('section');box.className='svm-area-filter';areaFilters.mount(box);return box;}
 function installFilters(){const root=map.getContainer();const toggle=root.querySelector('.tree-filter-control__toggle');if(toggle&&!toggle.classList.contains('has-active-filters')&&toggle.title!=='Filtr bodů a ploch'){toggle.title='Filtr bodů a ploch';toggle.setAttribute('aria-label','Filtr bodů a ploch');}const content=root.querySelector('.tree-filter-control__content');if(content&&!content.querySelector('.svm-area-filter'))content.append(filterFields());}
 // Use the existing toggle handlers, so panel state remains consistent.
 const closePanels=e=>{if(e.target.closest('.leaflet-control,.leaflet-popup'))return;for(const cls of ['tree-filter-control','tree-marker-color-control']){const panel=map.getContainer().querySelector('.'+cls);if(panel&&!panel.classList.contains(cls+'--collapsed'))panel.querySelector('.'+cls+'__toggle')?.click();}};
 map.getContainer().addEventListener('click',closePanels);
 let styleInputs=null;
 function persist(){if(storageKey)try{localStorage.setItem(storageKey,JSON.stringify(state));}catch{}applyStyle();}
 function syncInputs(){if(!styleInputs)return;styleInputs.color.value=state.color;styleInputs.pattern.value=state.pattern;styleInputs.range.value=state.transparency;styleInputs.output.textContent=state.transparency+' %';}
 function installStyle(){
  const content=map.getContainer().querySelector('.tree-marker-color-control__content');if(!content||content.querySelector('.svm-area-style'))return;
  const section=el('section');section.className='svm-area-style';section.append(el('strong','Plochy porostů'));
  const palette=el('div');palette.className='svm-palette';
  for(const hex of ['#6a852e','#2d7d32','#19a7a0','#2979c9','#8658b8','#d34b48','#e49b20','#737373']){const b=el('button');b.type='button';b.style.backgroundColor=hex;b.title='Barva '+hex;b.setAttribute('aria-label','Barva ploch '+hex);b.onclick=()=>{state.color=hex;syncInputs();persist();};palette.append(b);}section.append(palette);
  const color=el('input');color.type='color';color.setAttribute('aria-label','Vlastní barva ploch');color.oninput=()=>{state.color=color.value;persist();};section.append(color);
  const label=el('label','Výplň ploch'),pattern=el('select');for(const [v,t] of [['solid','Plná barva'],['diagonal','Šikmé šrafování'],['cross','Křížové šrafování']])pattern.append(new Option(t,v));label.append(pattern);section.append(label);pattern.onchange=()=>{state.pattern=pattern.value;persist();};
  const opacityLabel=el('label','Průhlednost výplně '),output=el('output'),range=el('input');range.type='range';range.min='0';range.max='100';range.step='5';opacityLabel.append(output,range);section.append(opacityLabel);range.oninput=()=>{state.transparency=Number(range.value);output.textContent=state.transparency+' %';persist();};
  section.append(el('small','Platí pro všechny plochy. Nastavení se pamatuje v tomto prohlížeči pro daný projekt.'));content.append(section);styleInputs={color,pattern,range,output};syncInputs();L.DomEvent.disableClickPropagation(section);L.DomEvent.disableScrollPropagation(section);
 }
 function installTabs(){
  const panel=document.getElementById('tablePanelContent');if(!panel||panel.querySelector('.svm-table-tabs'))return;
  const points=el('div');points.className='svm-point-panel';points.id='svmPointsPanel';
  while(panel.firstChild)points.append(panel.firstChild);
  const pointFilters=el('details');pointFilters.append(el('summary','Filtrování mapy a tabulky'));const pointHost=el('div');pointFilters.append(pointHost);TreeApp.projectTreeFilters.mount(pointHost);points.prepend(pointFilters);
  const areas=el('div');areas.className='svm-area-panel';areas.id='svmAreasPanel';areas.hidden=true;
  const actions=el('div');actions.className='svm-area-actions';const manage=el('a','Otevřít správu ploch');manage.href='/map/public/areas/';actions.append(manage);tableStatus=el('span','Načítám plochy…');actions.append(tableStatus);areas.append(actions,filterFields());
  const table=el('table');table.className='svm-area-table';const head=el('thead'),row=el('tr');for(const name of ['Číslo plochy','Název','Výměra z mapy (m²)','Stav','Datum měření','Další kontrola','Otevřít'])row.append(el('th',name));head.append(row);tableBody=el('tbody');table.append(head,tableBody);areas.append(table);
  const tabs=el('div');tabs.className='svm-table-tabs';tabs.setAttribute('role','tablist');tabs.setAttribute('aria-label','Typ záznamů');
  const pointButton=el('button','Body'),areaButton=el('button','Plochy');
  const choose=showAreas=>{points.hidden=showAreas;areas.hidden=!showAreas;pointButton.setAttribute('aria-selected',String(!showAreas));areaButton.setAttribute('aria-selected',String(showAreas));const label=document.querySelector('.table-panel__label');if(label)label.textContent=showAreas?'Seznam ploch':'Seznam stromů';if(!showAreas&&window.jQuery?.fn?.dataTable?.isDataTable('#tabulka'))jQuery('#tabulka').DataTable().columns.adjust();window.dispatchEvent(new Event('resize'));};
  for(const [button,target,isArea] of [[pointButton,points,false],[areaButton,areas,true]]){button.type='button';button.id=isArea?'svmAreasTab':'svmPointsTab';button.setAttribute('role','tab');button.setAttribute('aria-controls',target.id);target.setAttribute('role','tabpanel');target.setAttribute('aria-labelledby',button.id);button.onclick=()=>choose(isArea);tabs.append(button);}
  tabs.onkeydown=e=>{if(['ArrowLeft','ArrowRight','Home','End'].includes(e.key)){e.preventDefault();const isArea=e.key==='End'||e.key!=='Home'&&document.activeElement===pointButton;choose(isArea);(isArea?areaButton:pointButton).focus();}};
  panel.append(tabs,points,areas);choose(false);renderRows();
 }
 function renderRows(){if(!tableBody)return;tableBody.replaceChildren();for(const f of filtered()){const p=f.properties,a=p.attributes,row=el('tr');for(const value of [p.code,a.name||'',Number(p.area_m2).toLocaleString('cs-CZ',{maximumFractionDigits:2}),a.status==='removed'?'Odstraněný':'Existující',a.measured_at||'',a.next_insp_at||''])row.append(el('td',value));const cell=el('td'),open=el('a','Karta');open.href='/map/public/project_tools/area_card.php?id='+Number(p.id);const zoom=el('button','V mapě');zoom.type='button';zoom.onclick=()=>{if(!map.hasLayer(polygons))polygons.addTo(map);polygons.eachLayer(l=>{if(l.feature.properties.id===p.id){map.fitBounds(l.getBounds(),{padding:[30,30],maxZoom:20});l.openPopup();}});};cell.append(open,document.createTextNode(' '),zoom);row.append(cell);tableBody.append(row);}if(tableStatus)tableStatus.textContent='Počet ploch: '+filtered().length+' / '+features.length;}
 let pending=false;
 async function load(){if(pending)return;pending=true;try{const r=await fetch('/map/public/areas/api.php?action=list',{cache:'no-store'});const d=await r.json();if(!r.ok)throw new Error(d.error||'Plochy nelze načíst.');features=d.features;const key='svm-area-style:'+d.project_id;if(storageKey!==key){storageKey=key;try{const saved=JSON.parse(localStorage.getItem(key));if(valid(saved))state=saved;}catch{}syncInputs();}applyFilter();}catch(e){if(tableStatus)tableStatus.textContent='Plochy se nenačetly. Otevřete jejich správu.';console.warn('Plochy:',e.message);}finally{pending=false;}}
 // Marker controls and DataTables initialize later in main.js.
 const observer=new MutationObserver(()=>{installStyle();installFilters();});observer.observe(map.getContainer(),{childList:true,subtree:true});
 setTimeout(()=>{installTabs();installStyle();installFilters();load();},0);
 const visible=()=>{if(!document.hidden)load();};document.addEventListener('visibilitychange',visible);map.once('unload',()=>{observer.disconnect();document.removeEventListener('visibilitychange',visible);});
 return result;
};
})();

