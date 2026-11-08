'use strict';
(async function () {
const boot=window.AreasBoot, $=s=>document.querySelector(s), form=$('#areaForm');
const status=(text,error=false)=>{ $('#status').textContent=text;$('#status').classList.toggle('error',error); };
if(!window.L || !L.Draw){status('Nepodařilo se načíst mapové knihovny. Zkontrolujte připojení a obnovte stránku.',true);return;}
$('#projectName').textContent=boot.project_name;
$('#areaLocation').append(document.querySelector('.map-column'));
let dirty=false,busy=false,selected=null,editingLayer=null,features=[];
let loadSerial=0,editLoadFailed=false,photoNotesAvailable=true;
const requestedId=new URLSearchParams(location.search).get('id');


const fields=['name','status','height_min','height_max','suitability','management','biology','attractiveness','state_note','treatments','urgency','note','planted','measured_at','last_insp_at','next_insp_at'];
const types={shrubs_low:['Keře nízké','Druhy dorůstající přibližně do jednoho metru.'],shrubs_high:['Keře střední a vysoké','Druhy dorůstající nad jeden metr.'],climbers:['Liány','Dřevité popínavky.'],young:['Mladý porost','Rané fáze od kultury či náletu po tyčkovinu.'],middle:['Porost středního věku','Výčetní tloušťka zpravidla 7–25 cm.'],mature:['Dospívající a dospělý porost','Převažují obvody nad 80 cm ve výšce 1,3 m.'],layered:['Věkově a prostorově diferencovaný porost','Více vývojových fází a výrazné výškové členění.']};
const categories={
 suitability:['Vhodnost',{'invasive':'Porost invazivních dřevin','undesirable':'Nežádoucí','other':'Ostatní','suitable':'Vhodný'},'Posuzujte dominanci invazivních druhů a soulad skladby se stanovištěm a funkcí.'],
 management:['Pěstební stav',{'neglected':'Pěstebně zanedbaný','irregular':'Průběžně nevychovávaný','managed':'Vychovávaný'},'Rozlišujte zanedbání, nedostatečně opakovanou péči a pravidelnou výchovu. Přirozeně řídký porost nemusí zásahy potřebovat.'],
 biology:['Biologická hodnota',{'low':'Nízká','medium':'Střední','high':'Vysoká'},'Zvažte pestrost, etáže a biotopy. Monokultura obvykle nízká; smíšený víceetážový porost střední; cenná stanoviště organismů vysoká.'],
 attractiveness:['Atraktivita umístění',{'low':'Méně významná','medium':'Střední','high':'Vysoká'},'Hodnoťte vizuální a prostorový význam i návštěvnost: od zanedbatelného působení po významnou dominantu.']
};
function node(tag,text,cls){const n=document.createElement(tag);if(text!==undefined)n.textContent=text;if(cls)n.className=cls;return n;}
function button(text,fn){const b=node('button',text);b.type='button';b.onclick=fn;return b;}
function help(text){const d=node('details');d.append(node('summary','ⓘ Nápověda'),node('p',text));return d;}
function inputLabel(parent,title,input){const l=node('label',title);l.append(input);parent.append(l);return input;}
function makeInput(type,value=''){const i=node('input');i.type=type;i.value=value??'';return i;}
for(const [key,[label,options,hint]] of Object.entries(categories)){
 const s=node('select');s.name=key;s.append(new Option('Nehodnoceno',''));for(const [v,t] of Object.entries(options))s.append(new Option(t,v));inputLabel($('#aopkFields'),label,s);$('#aopkFields').append(help(hint));
}
const map=L.map('areaMap',{maxZoom:22}).setView([49.8,15.5],7);
const osm=L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:22,maxNativeZoom:19,attribution:'© OpenStreetMap'}).addTo(map);
const ortho=L.tileLayer.wms('https://ags.cuzk.gov.cz/arcgis1/services/ORTOFOTO/MapServer/WMSServer',{layers:'0',format:'image/jpeg',version:'1.3.0',maxZoom:22,attribution:'© ČÚZK'});
const cadastral=L.tileLayer.wms('https://services.cuzk.gov.cz/wms/local-KM-wms.asp',{layers:'KN',format:'image/png',transparent:true,version:'1.3.0',maxZoom:22,attribution:'© ČÚZK'});
const white=L.gridLayer();white.createTile=()=>{const tile=document.createElement('div');tile.style.background='white';return tile;};
L.control.layers({'OpenStreetMap':osm,'Ortofoto ČÚZK':ortho,'Bílý podklad':white},{'Katastrální mapa ČÚZK':cadastral}).addTo(map);
const background=L.featureGroup().addTo(map), editGroup=L.featureGroup().addTo(map);
async function api(action,body,method='GET'){
 const options={method,credentials:'same-origin',cache:'no-store',headers:{'X-CSRF-Token':boot.csrf,'X-Project-Id':String(boot.project_id)}};
 if(body!==undefined){options.body=body instanceof FormData?body:JSON.stringify(body);if(!(body instanceof FormData))options.headers['Content-Type']='application/json';}
 const res=await fetch('api.php?action='+action,options);let data;
 try{data=await res.json();}catch{throw new Error('Server nevrátil data. Může být nutné nové přihlášení.');}
 if(!res.ok)throw new Error(data.error||'Operace selhala.');
 if(data.project_id && data.project_id!==boot.project_id)throw new Error('Aktivní projekt se změnil. Otevřete tuto stránku znovu.');
 return data;
}
const snapIndicator=L.circleMarker([0,0],{radius:7,color:'#e85b00',weight:3,fillColor:'#fff',fillOpacity:1,interactive:false});
function hideSnap(){if(map.hasLayer(snapIndicator))map.removeLayer(snapIndicator);}
function snapPoint(latlng,preview=false){
 if(!latlng||!$('#snapAreas').checked||map.getZoom()<15){if(preview)hideSnap();return latlng;}
 const p=map.project(latlng,map.getZoom());let vertex=null,edge=null,vd=20,ed=16;
 for(const f of features){if(f.properties.id===selected?.properties.id)continue;
 for(const ring of f.geometry.coordinates){for(let i=0;i<ring.length-1;i++){
 const av=L.latLng(ring[i][1],ring[i][0]),bv=L.latLng(ring[i+1][1],ring[i+1][0]);
 const a=map.project(av,map.getZoom()),b=map.project(bv,map.getZoom());
 const dist=p.distanceTo(a);if(dist<vd){vd=dist;vertex=av;}
 const dx=b.x-a.x,dy=b.y-a.y,den=dx*dx+dy*dy,t=den?Math.max(0,Math.min(1,((p.x-a.x)*dx+(p.y-a.y)*dy)/den)):0;
 const q=L.point(a.x+t*dx,a.y+t*dy),d=p.distanceTo(q);if(d<ed){ed=d;edge=map.unproject(q,map.getZoom());}
 }}}
 const target=vertex||edge;if(preview){if(target)snapIndicator.setLatLng(target).addTo(map);else hideSnap();}
 return target||latlng;
}
const boundary=createAreaBoundaryEditor({map,canEdit:boot.can_edit,snap:snapPoint,status,isBusy:()=>busy||editLoadFailed,
 onActive:active=>{if(editingLayer){if(active)editGroup.removeLayer(editingLayer);else editGroup.addLayer(editingLayer);}if(!active)updateBoundaryButtons();},
 onComplete:g=>{setGeometry(g);updateBoundaryButtons();mark();status('Hranice dokončena. Změny potvrďte uložením formuláře.');}
});
function updateBoundaryButtons(){$('#editArea').hidden=!editingLayer;$('#drawArea').textContent=editingLayer?'Překreslit hranici':'Nakreslit hranici';$('#drawArea').hidden=!boot.can_edit;$('#editArea').disabled=!boot.can_edit;}
function updateLabels(){background.eachLayer(l=>{const n=l.getTooltip()?.getElement();if(n)n.style.display=map.getZoom()>=15?'':'none';});}
map.on('zoomend',updateLabels);
function mark(){dirty=true;}
form.addEventListener('input',mark);form.addEventListener('change',mark);
window.addEventListener('beforeunload',e=>{if(dirty||busy||boundary.active||photoNotesDirty()){e.preventDefault();e.returnValue='';}});
function closeEditor(){if(busy){status('Právě probíhá ukládání nebo načítání. Vyčkejte.',true);return;}if(!mayLeave())return;stopDrawing();stopEditing();dirty=false;location.href='../index.php';}
$('#closeEditor').onclick=$('#closeEditorBottom').onclick=closeEditor;
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!document.querySelector('dialog[open]')){e.preventDefault();e.stopImmediatePropagation();closeEditor();}},true);
function photoNotesDirty(){return !!$('#photos').querySelector('[data-note-dirty="1"]');}
function mayLeave(){return !busy && (!(dirty||boundary.active||photoNotesDirty()) || confirm('Máte neuložené změny. Opravdu je zahodit?'));}
function stopEditing(){boundary.reset();}
function stopDrawing(){boundary.reset();}
function mapArea(){return editingLayer?Math.round(L.GeometryUtil.geodesicArea(editingLayer.getLatLngs()[0])*100)/100:0;}
function updateArea(){$('#mapArea').textContent=editingLayer?mapArea().toLocaleString('cs-CZ',{maximumFractionDigits:2}):'—';}
function setGeometry(g){editGroup.clearLayers();editingLayer=null;if(g)L.geoJSON(g,{style:{color:'#b16c12',weight:3,fillOpacity:.22}}).eachLayer(l=>{editingLayer=l;editGroup.addLayer(l);l.on('edit',()=>{mark();updateArea();});});updateArea();}
function repaint(){background.clearLayers();for(const f of features){if(f.properties.id===selected?.properties.id)continue;const removed=f.properties.attributes.status==='removed';L.geoJSON(f,{interactive:false,style:{color:removed?'#a94137':'#44855a',fillOpacity:.14,dashArray:removed?'6 5':null}}).eachLayer(l=>{l.bindTooltip(node('span',f.properties.code),{permanent:true,direction:'center',className:'area-label'});background.addLayer(l);});}updateLabels();}
function updateList(){const list=$('#areaList');list.replaceChildren(new Option('— Vyberte plochu —',''));for(const f of features){const p=f.properties;list.append(new Option(p.code+(p.attributes.name?' – '+p.attributes.name:''),p.id));}list.value=selected?.properties.id||'';}
function total(){let sum=0;$('#componentRows').querySelectorAll('.component-row').forEach(r=>{sum+=Number(r.querySelector('input').value)||0;});$('#componentTotal').textContent='Součet výměr pro AOPK: '+sum.toLocaleString('cs-CZ')+' m²';}
function addComponent(data={}){
 const row=node('div',undefined,'row component-row'),sel=node('select');for(const [v,[t]] of Object.entries(types))sel.append(new Option(t,v));sel.value=data.type||'shrubs_low';
 inputLabel(row,'Charakter porostu',sel);const hint=node('p',types[sel.value][1],'hint');row.append(hint);sel.onchange=()=>{hint.textContent=types[sel.value][1];mark();};
 const val=makeInput('number',data.area_m2);val.min=0;val.max=1e9;val.step='.01';val.required=true;inputLabel(row,'Výměra pro ocenění (m²)',val);val.oninput=total;
 row.append(button('Převzít výměru z mapy',()=>{if(!editingLayer){status('Nejdříve zakreslete hranici.',true);return;}val.value=mapArea();mark();total();}),button('Odebrat charakter',()=>{row.remove();mark();total();}));$('#componentRows').append(row);total();
}
function addSpecies(data={}){
 const row=node('div',undefined,'row species-row'),latin=makeInput('text',data.scientific_name),czech=makeInput('text',data.czech_name),share=makeInput('number',data.share);latin.maxLength=czech.maxLength=250;latin.autocomplete=czech.autocomplete='off';share.min=0;share.max=100;share.step='.1';
 inputLabel(row,'Latinský název',latin);inputLabel(row,'Český název',czech);const list=node('div',undefined,'suggest');row.append(list);inputLabel(row,'Zastoupení (%)',share);
 let serial=0,timer;
 for(const inp of [latin,czech])inp.addEventListener('input',()=>{const seq=++serial;clearTimeout(timer);list.replaceChildren();const q=inp.value.trim();if(q.length<2)return;timer=setTimeout(async()=>{try{const res=await fetch('../species_search.php?q='+encodeURIComponent(q));if(!res.ok)throw new Error();const values=await res.json();if(seq!==serial||!row.isConnected)return;for(const item of (Array.isArray(values)?values:[])){list.append(button((item.czech_name||'')+' — '+item.scientific_name,()=>{serial++;latin.value=item.scientific_name||'';czech.value=item.czech_name||'';list.replaceChildren();mark();}));}}catch{if(seq===serial)list.replaceChildren(node('p','Návrhy se nepodařilo načíst. Název můžete zadat ručně.'));}},200);});
 row.addEventListener('focusout',()=>setTimeout(()=>{if(!row.contains(document.activeElement)){serial++;list.replaceChildren();}},0));
 row.append(button('Odebrat druh',()=>{serial++;clearTimeout(timer);row.remove();mark();}));$('#speciesRows').append(row);
}
function nextCode(){const used=new Set(features.map(f=>f.properties.code.toUpperCase()));let n=1;while(used.has(String(n)))n++;return String(n);}
function fill(f){editLoadFailed=false;selected=f;boundary.reset();$('#areaCardLink').hidden=!f;if(f)$('#areaCardLink').href='../project_tools/area_card.php?id='+Number(f.properties.id);$('#saveHistory').hidden=!f||!boot.can_edit;$('#deleteArea').hidden=!f||!boot.can_edit;form.reset();$('#speciesRows').replaceChildren();$('#componentRows').replaceChildren();const a=f?.properties.attributes||{};form.elements.code.value=f?.properties.code||nextCode();for(const k of fields)form.elements[k].value=a[k]??(k==='status'?'existing':'');for(const row of a.species||[])addSpecies(row);for(const row of a.components||[])addComponent(row);setGeometry(f?.geometry);total();$('#formTitle').textContent=f?'Upravit plochu '+f.properties.code:'Nová plocha';$('#pageMode').textContent=f?'Editace plochy':'Přidání plochy';$('#save').textContent=f?'Uložit změny':'Přidat plochu';$('#photos').replaceChildren();$('#photoFiles').value='';$('#cameraFile').value='';$('#photoSelection').textContent='';$('#pickPhotos').disabled=$('#takePhoto').disabled=$('#cameraFile').disabled=!f||!boot.can_edit;$('#editArea').disabled=!f&&!editingLayer||!boot.can_edit;$('#photoFiles').disabled=!f||!boot.can_edit;$('#photoHelp').textContent=f?'JPG, PNG nebo WebP, nejvýše 5 MB na fotografii. Limit hostingu může být nižší.':'Nejdříve uložte plochu, potom přidejte fotografie.';dirty=false;repaint();updateList();updateRemoved();updateBoundaryButtons();$('#historyRows').replaceChildren();$('#moreHistory').hidden=true;}
function showPhotos(photos,notesAvailable=true){photoNotesAvailable=notesAvailable;$('#photos').replaceChildren();for(const p of photos){const fig=node('figure'),a=node('a'),img=node('img');a.href='api.php?action=photo&id='+Number(p.id);a.target='_blank';a.rel='noopener';img.src=a.href;img.alt=p.original_name;img.loading='lazy';a.append(img);fig.append(a,node('figcaption',p.original_name));const caption=node('textarea');caption.value=p.note||'';caption.maxLength=1000;caption.placeholder='Popisek fotografie';caption.setAttribute('aria-label','Popisek fotografie '+p.original_name);caption.disabled=!boot.can_edit||!photoNotesAvailable;caption.oninput=()=>caption.dataset.noteDirty=caption.value===(p.note||'')?'0':'1';fig.append(caption);if(boot.can_edit&&photoNotesAvailable)fig.append(button('Uložit popisek',async()=>{if(busy)return;busy=true;caption.disabled=true;try{const d=await api('photo_note',{id:p.id,note:caption.value,previous:p.note||''},'POST');p.note=d.note;caption.dataset.noteDirty='0';status('Popisek fotografie byl uložen.');}catch(e){status(e.message,true);}finally{caption.disabled=false;busy=false;}}));if(boot.can_edit)fig.append(button('Odstranit fotku',async()=>{if(busy||!confirm('Odstranit tuto fotografii?'))return;busy=true;try{await api('remove_photo',{id:p.id},'POST');fig.remove();status('Fotografie odstraněna.');}catch(e){status(e.message,true);}finally{busy=false;}}));if(!photoNotesAvailable)fig.append(node('p','Popisky vyžadují aktualizaci databáze: 05_popisky_fotek_ploch.sql.','hint'));$('#photos').append(fig);}}
async function choose(id){if(!mayLeave()){$('#areaList').value=selected?.properties.id||'';return;}const seq=++loadSerial;busy=true;editLoadFailed=true;$('#fields').disabled=true;$('#pageMode').textContent='Editace plochy';$('#formTitle').textContent='Načítám plochu k editaci…';$('#save').textContent='Uložit změny';status('Načítám údaje plochy…');try{const d=await api('get&id='+Number(id));if(seq!==loadSerial)return;stopDrawing();fill(d.feature);showPhotos(d.photos,d.photo_notes_available!==false);loadHistory();if(editGroup.getBounds().isValid())map.fitBounds(editGroup.getBounds(),{padding:[25,25],maxZoom:20});status(d.photo_notes_available===false?'Plocha načtena. Pro popisky fotografií je potřeba spustit 05_popisky_fotek_ploch.sql.':boot.can_edit?'Plocha načtena.':'Plocha načtena pouze pro prohlížení.');}catch(e){editLoadFailed=true;$('#formTitle').textContent='Editace plochy – načtení se nezdařilo';status(e.message+' Obnovte stránku pro opakování načtení. Nový záznam se nezaloží.',true);}finally{busy=false;$('#fields').disabled=!boot.can_edit||editLoadFailed;}}
$('#areaList').onchange=()=>{if($('#areaList').value)choose($('#areaList').value);};
$('#newArea').onclick=()=>{if(!mayLeave())return;loadSerial++;stopDrawing();fill(null);status('Vyplňte údaje a zakreslete novou plochu.');};
$('#allAreas').onclick=()=>{const bounds=L.latLngBounds([]);features.forEach(f=>bounds.extend(L.geoJSON(f).getBounds()));if(bounds.isValid())map.fitBounds(bounds,{padding:[25,25],maxZoom:20});};
$('#addSpecies').onclick=()=>{addSpecies();mark();};$('#addComponent').onclick=()=>{if($('#componentRows').children.length>=7){status('Lze zadat nejvýše sedm charakterů.',true);return;}addComponent();mark();};
$('#drawArea').onclick=()=>{if(busy||!boot.can_edit||boundary.active)return;if(editingLayer&&!confirm('Překreslit hranici? Původní zůstane zachovaná, dokud novou nepotvrdíte.'))return;boundary.start(null);map.getContainer().scrollIntoView({block:'center',behavior:'smooth'});};
$('#editArea').onclick=()=>{if(busy||!boot.can_edit||!editingLayer)return;boundary.start(editingLayer.toGeoJSON().geometry);map.getContainer().scrollIntoView({block:'center',behavior:'smooth'});};
form.onsubmit=async e=>{e.preventDefault();if(busy||!boot.can_edit||editLoadFailed)return;if(photoNotesDirty()){status('Nejdříve uložte rozepsané popisky fotografií.',true);return;}if(boundary.active){status('Nejdříve dokončete rozpracovanou hranici.',true);return;}if(!editingLayer){status('Nejdříve zakreslete hranici.',true);return;}stopDrawing();stopEditing();const a={};for(const k of fields)a[k]=form.elements[k].value;
 a.species=[...$('#speciesRows').children].map(r=>{const v=r.querySelectorAll('input');return {scientific_name:v[0].value,czech_name:v[1].value,share:v[2].value};});
 a.components=[...$('#componentRows').children].map(r=>({type:r.querySelector('select').value,area_m2:r.querySelector('input').value}));
 const preserved=JSON.parse(form.elements.preserve_fields?.value||'[]');if(selected)for(const k of preserved)if(Object.hasOwn(selected.properties.attributes,k))a[k]=selected.properties.attributes[k];
 const manualHistory=e.submitter?.id==='saveHistory';
 const payload={history_snapshot:manualHistory,id:selected?.properties.id||0,version:selected?.properties.version||0,code:form.elements.code.value,attributes:a,geometry:editingLayer.toGeoJSON().geometry};busy=true;$('#fields').disabled=true;
 try{const d=await api('save',payload,'POST');const old=features.findIndex(f=>f.properties.id===d.feature.properties.id);if(old>=0)features[old]=d.feature;else features.push(d.feature);fill(d.feature);history.replaceState(null,'','?id='+d.feature.properties.id);status(manualHistory?'Aktuální stav plochy byl uložen do historie.':'Plocha '+d.feature.properties.code+' byla uložena.');if(manualHistory)$('#areaHistory').open=true;try{const current=await api('get&id='+d.feature.properties.id);showPhotos(current.photos,current.photo_notes_available!==false);loadHistory();}catch{status('Plocha je uložená, ale fotografie se nepodařilo načíst.',true);}}
 catch(err){status(err.message,true);}finally{busy=false;$('#fields').disabled=!boot.can_edit;}};
function updateRemoved(){const removed=form.elements.status.value==='removed';$('#removedArea').hidden=!selected||!boot.can_edit;$('#removedArea').textContent=removed?'Obnovit existující porost':'Označit porost jako odstraněný';$('#removedHint').textContent=removed?'Porost je označen jako odstraněný. Záznam, hranice i fotografie zůstávají.':'';}
$('#removedArea').onclick=()=>{if(busy||editLoadFailed||!selected||!boot.can_edit)return;const removed=form.elements.status.value==='removed';if(!confirm(removed?'Obnovit porost? Změnu poté potvrďte uložením.':'Označit porost jako odstraněný? Záznam zůstane. Změnu poté potvrďte uložením.'))return;form.elements.status.value=removed?'existing':'removed';mark();updateRemoved();status('Změnu stavu potvrďte tlačítkem Uložit změny.');};
let historyBefore=0;
async function loadHistory(more=false){if(!selected)return;const id=selected.properties.id;try{const d=await api('history&id='+id+(more?'&before='+historyBefore:''));if(selected?.properties.id!==id)return;if(!more)$('#historyRows').replaceChildren();for(const r of d.rows){const detail=node('details');detail.append(node('summary',r.created_at+' · Uložil: '+(r.username||'uživatel #'+r.user_id)+' · '+({'created':'Vytvoření','updated':'Úprava','table':'Úprava v tabulce','baseline':'Stav před první zaznamenanou změnou','manual':'Uložený historický záznam'}[r.action]||r.action)));const snapshot=r.snapshot,table=node('table');const labels={code:'Číslo plochy',name:'Název',status:'Stav',height_min:'Výška od',height_max:'Výška do',state_note:'Popis stavu',treatments:'Navržené zásahy',urgency:'Naléhavost',note:'Poznámka',planted:'Datum výsadby',measured_at:'Datum měření',last_insp_at:'Poslední kontrola',next_insp_at:'Další kontrola',area_m2:'Výměra z mapy (m²)',species:'Druhové složení',components:'Charaktery porostu'};for(const [k,v] of Object.entries({code:snapshot.code,...snapshot.attributes,area_m2:snapshot.area_m2})){let text=v??'—';if(k==='status')text=v==='removed'?'Odstraněný':'Existující';else if(k==='species')text=(v||[]).map(x=>[x.scientific_name,x.czech_name,x.share!=null?x.share+' %':''].filter(Boolean).join(' · ')).join('; ');else if(k==='components')text=(v||[]).map(x=>(types[x.type]?.[0]||x.type)+': '+x.area_m2+' m²').join('; ');else if(categories[k])text=categories[k][1][v]||v;const tr=node('tr');tr.append(node('th',labels[k]||categories[k]?.[0]||k),node('td',String(text)));table.append(tr);}detail.append(table);if(snapshot.geometry){const geo=node('details');geo.append(node('summary','Souřadnice historické hranice (GeoJSON)'),node('pre',JSON.stringify(snapshot.geometry)));detail.append(geo);}$('#historyRows').append(detail);}if(!more&&!d.rows.length)$('#historyRows').append(node('p','Zatím nejsou zaznamenané změny.'));historyBefore=d.rows.at(-1)?.id||0;$('#moreHistory').hidden=!d.more;}catch(e){$('#historyRows').textContent='Historii se nepodařilo načíst: '+e.message;}}
$('#moreHistory').onclick=()=>loadHistory(true);
$('#deleteArea').onclick=async()=>{
 if(busy||editLoadFailed||!selected||!boot.can_edit)return;
 const id=selected.properties.id,code=selected.properties.code,version=selected.properties.version;
 if(!confirm('Trvale smazat plochu '+code+' včetně všech jejích fotografií? Tuto akci nelze vrátit. Pokud byl porost pouze odstraněn v terénu, použijte raději tlačítko Označit porost jako odstraněný.'))return;
 busy=true;$('#fields').disabled=true;stopDrawing();stopEditing();
 try{await api('delete',{id,version},'POST');features=features.filter(f=>f.properties.id!==id);dirty=false;location.href='../index.php';}
 catch(e){status(e.message,true);}finally{busy=false;$('#fields').disabled=!boot.can_edit;}
};
$('#pickPhotos').onclick=()=>{if(!busy&&!photoNotesDirty())$('#photoFiles').click();else if(photoNotesDirty())status('Nejdříve uložte popisky fotografií.',true);};
$('#takePhoto').onclick=()=>{if(!busy&&!photoNotesDirty())$('#cameraFile').click();else if(photoNotesDirty())status('Nejdříve uložte popisky fotografií.',true);};
for(const input of [$('#photoFiles'),$('#cameraFile')])input.onchange=async()=>{
 if(busy||editLoadFailed||!selected||!boot.can_edit)return;const files=[...input.files];input.value='';if(!files.length)return;
 const id=selected.properties.id;busy=true;$('#pickPhotos').disabled=$('#takePhoto').disabled=true;let done=0;
 try{for(const f of files){if(f.size>5*1024*1024)throw new Error(f.name+': soubor přesahuje 5 MB.');
 $('#photoSelection').textContent='Nahrávám '+(done+1)+'/'+files.length+': '+f.name;
 const body=new FormData();body.append('area_id',id);body.append('photo',f);await api('upload',body,'POST');done++;}
 status('Nahráno fotografií: '+done+'.');$('#photoSelection').textContent='Nahráno fotografií: '+done+'.';}
 catch(e){const msg='Nahráno '+done+' z '+files.length+'. '+e.message+' Znovu vyberte jen nenahrané snímky: '+files.slice(done).map(f=>f.name).join(', ');status(msg,true);$('#photoSelection').textContent=msg;}
 finally{try{const current=await api('get&id='+id);showPhotos(current.photos,current.photo_notes_available!==false);}catch{status('Fotografie se nepodařilo znovu načíst. Obnovte stránku.',true);}busy=false;$('#pickPhotos').disabled=$('#takePhoto').disabled=false;}
};
if(!boot.can_edit){$('#fields').disabled=true;$('#newArea').hidden=true;$('#formTitle').textContent='Plochy projektu';}
if(window.ResizeObserver)new ResizeObserver(()=>map.invalidateSize({pan:false})).observe($('#areaMap'));
if(requestedId){editLoadFailed=true;$('#fields').disabled=true;$('#pageMode').textContent='Editace plochy';$('#formTitle').textContent='Načítám plochu k editaci…';$('#save').textContent='Uložit změny';}
try{const d=await api('list');features=d.features;if(!requestedId)fill(null);else{repaint();updateList();}if(!boot.can_edit)$('#fields').disabled=true;$('#allAreas').click();const params=new URLSearchParams(location.search);const lat=Number(params.get('lat')),lng=Number(params.get('lng')),zoom=Number(params.get('zoom'));if(params.has('lat')&&params.has('lng')&&Number.isFinite(lat)&&Math.abs(lat)<85&&Number.isFinite(lng)&&Math.abs(lng)<=180)map.setView([lat,lng],Math.max(3,Math.min(22,zoom||18)));const id=requestedId;if(id)await choose(id);else status(boot.can_edit?'Můžete přidat novou plochu.':'Režim prohlížení.');}
catch(e){status(e.message,true);$('#save').disabled=true;}
if(boot.can_edit && !editLoadFailed && !requestedId && !$('#save').disabled && new URLSearchParams(location.search).get('new')==='1') $('#drawArea').click();
})();
