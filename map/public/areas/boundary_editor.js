'use strict';
// One working geometry and undo stack for mouse, crosshair and GNSS.
window.createAreaBoundaryEditor=function({map,canEdit,snap,onComplete,onActive,status,isBusy}){
 const $=id=>document.getElementById(id),group=L.featureGroup().addTo(map),handles=L.featureGroup().addTo(map);
 let active=false,points=[],undo=[],redo=[],selected=-1,insertion=null,mode='mouse',watch=null,watchSerial=0,fix=null,dragBefore=null;
 const cross=document.createElement('div');cross.className='survey-cross';cross.textContent='＋';cross.hidden=true;map.getContainer().append(cross);
 const gnss=L.circleMarker([0,0],{radius:6,color:'#175bcb',fillOpacity:1,interactive:false});
 const clone=ps=>ps.map(p=>L.latLng(p.lat,p.lng));
 const state=()=>({points:clone(points),selected,insertion});
 const blocked=()=>!active||!canEdit||isBusy();
 function record(){redo=[];undo.push(state());if(undo.length>100)undo.shift();}
 function controls(){
  $('boundaryTools').hidden=!active;$('drawArea').hidden=$('editArea').hidden=active;
  cross.hidden=!active||mode!=='cross';$('applyVertex').hidden=mode==='mouse';
  $('applyVertex').textContent=(selected>=0?'Přesunout vybraný vrchol':insertion!==null?'Vložit vrchol do hrany':'Přidat vrchol')+(mode==='gnss'?' z GNSS':' pod křížem');
  $('applyVertex').disabled=mode==='gnss'&&!fix;
  $('clearVertex').hidden=selected<0&&insertion===null;$('removeVertex').hidden=selected<0;
  $('undoVertex').disabled=!undo.length;$('redoVertex').disabled=!redo.length;document.querySelectorAll('[data-vertex-mode]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.vertexMode===mode)));if(document.getElementById('vertexModeBar'))$('vertexModeBar').hidden=!active;$('finishVertices').disabled=points.length<3;
  if(!active){$('boundaryHint').textContent='Spusťte kreslení nebo úpravu hranice pomocí tlačítka v mapě.';return;}
  $('boundaryHint').textContent='Vrcholů: '+points.length+'. '+(selected>=0?(mode==='cross'?'Klepnutím vložíte nový vrchol pod kříž za vybraný vrchol. Úchyt pod křížem podržte a táhněte pro přesun.':'Klepnutím vložíte nový vrchol za vybraný. Vybraný vrchol lze přesunout nebo odebrat.'):mode==='mouse'?'Kliknutím přidejte vrchol, úchytem na hraně vložte nový.':mode==='cross'?'Klepnutí kamkoli do mapy působí pod křížem: vybere vrchol, vloží bod do úchytu hrany nebo přidá nový. Podržení a tažení přesouvá úchyt pod křížem.':'Klepnutí do mapy přidá aktuální polohu GNSS.');
 }
 function icon(label,chosen=false,mid=false){return L.divIcon({className:'boundary-handle'+(chosen?' selected':'')+(mid?' midpoint':''),html:'',iconSize:mid?[14,14]:[18,18],iconAnchor:mid?[7,7]:[9,9]});}
 function line(){group.clearLayers();if(points.length>1)(points.length>2?L.polygon(points,{pane:'contextAreas',color:'#b16c12',weight:3,fillOpacity:.18,interactive:false}):L.polyline(points,{pane:'contextAreas',color:'#b16c12',weight:3,interactive:false})).addTo(group);}
 function render(){line();handles.clearLayers();points.forEach((p,i)=>{
  const m=L.marker(p,{icon:icon(String(i+1),i===selected),draggable:canEdit&&mode==='mouse',keyboard:true,bubblingMouseEvents:false,title:'Vrchol '+(i+1)}).addTo(handles);
  m.on('click',e=>{if(blocked())return;if(mode==='cross'){if(e.originalEvent)L.DomEvent.stopPropagation(e.originalEvent);crossClick();return;}if(e.originalEvent)L.DomEvent.stopPropagation(e.originalEvent);selected=i;insertion=null;render();});
  m.on('dragstart',()=>{dragBefore=state();selected=i;insertion=null;});
  m.on('drag',()=>{if(blocked())return;points[i]=snap(m.getLatLng(),true);m.setLatLng(points[i]);line();});
  m.on('dragend',()=>{if(dragBefore){redo=[];undo.push(dragBefore);dragBefore=null;}render();});
 });
 const n=points.length;for(let i=0;i<(n>2?n:n-1);i++){const a=points[i],b=points[(i+1)%n],pa=map.project(a),pb=map.project(b),mid=map.unproject(pa.add(pb).divideBy(2));let added=-1;
 const m=L.marker(mid,{icon:icon('',false,true),draggable:canEdit&&mode==='mouse',keyboard:true,bubblingMouseEvents:false,title:'Přidat vrchol kliknutím nebo tažením'}).addTo(handles);
 m.on('click',e=>{if(blocked()||added>=0)return;if(mode==='cross'){if(e.originalEvent)L.DomEvent.stopPropagation(e.originalEvent);crossClick();return;}if(e.originalEvent)L.DomEvent.stopPropagation(e.originalEvent);record();points.splice(i+1,0,L.latLng(mid));selected=i+1;insertion=null;render();});
 m.on('dragstart',()=>{if(blocked())return;record();added=i+1;points.splice(added,0,L.latLng(mid));selected=added;insertion=null;});
 m.on('drag',()=>{if(blocked()||added<0)return;points[added]=snap(m.getLatLng(),true);m.setLatLng(points[added]);line();});
 m.on('dragend',()=>{if(added>=0)render();});}

 controls();preview();highlightTarget();}
 function preview(){if(active&&mode==='cross')snap(map.getCenter(),true);else if(active&&mode==='gnss'&&fix)snap(L.latLng(fix.coords.latitude,fix.coords.longitude),true);else snap(null,true);}
 // A long press grabs the handle under the crosshair, not under the finger.
 let hold=null,suppressClickUntil=0;
 const mapElement=map.getContainer(),hover=L.circleMarker([0,0],{radius:12,color:'#dc7800',weight:2,fillOpacity:0,interactive:false});
 function targetAtCenter(){if(!active||mode!=='cross'||hold?.grabbed)return null;const c=map.latLngToContainerPoint(map.getCenter());let found=null,best=24;
 points.forEach((p,i)=>{const d=c.distanceTo(map.latLngToContainerPoint(p));if(d<best){best=d;found={index:i,mid:false,ll:p};}});
 if(found)return found;for(let i=0;i<(points.length>2?points.length:points.length-1);i++){const a=map.project(points[i]),b=map.project(points[(i+1)%points.length]),ll=map.unproject(a.add(b).divideBy(2)),d=c.distanceTo(map.latLngToContainerPoint(ll));if(d<best){best=d;found={index:i+1,mid:true,ll};}}return found;}
 function highlightTarget(){if(hold?.grabbed)return;const t=targetAtCenter();cross.classList.toggle('can-grab',!!t);if(t)hover.setLatLng(t.ll).addTo(map);else if(map.hasLayer(hover))map.removeLayer(hover);}
 function endHold(cancel=false){if(!hold)return;const h=hold;hold=null;clearTimeout(h.timer);if(h.grabbed){suppressClickUntil=Date.now()+700;if(cancel){points=clone(h.before.points);selected=h.before.selected;insertion=h.before.insertion;}else{redo=[];undo.push(h.before);if(undo.length>100)undo.shift();}if(h.dragEnabled)map.dragging.enable();if(h.zoomEnabled)map.touchZoom?.enable();if(mapElement.hasPointerCapture?.(h.id))mapElement.releasePointerCapture(h.id);cross.classList.remove('is-grabbing');render();if(!cancel)status('Vrchol umístěn. Krok zpět vrátí celé gesto.');}highlightTarget();}
 function pointerDown(e){if(e.pointerType!=='touch'&&e.pointerType!=='pen')return;if(hold){if(e.pointerId!==hold.id)endHold(true);return;}if(blocked()||mode!=='cross'||e.target.closest('.leaflet-control'))return;const target=targetAtCenter();if(!target)return;
 const h={id:e.pointerId,x:e.clientX,y:e.clientY,target,grabbed:false};hold=h;h.timer=setTimeout(()=>{if(hold!==h||blocked()||mode!=='cross')return;if(target.mid&&points.length>=1000){endHold(true);return;}map.stop();h.before=state();h.grabbed=true;h.center=map.project(map.getCenter(),map.getZoom());h.zoom=map.getZoom();h.dragEnabled=map.dragging.enabled();h.zoomEnabled=map.touchZoom?.enabled();map.dragging.disable();map.touchZoom?.disable();if(target.mid)points.splice(target.index,0,L.latLng(target.ll));selected=target.index;insertion=null;if(map.hasLayer(hover))map.removeLayer(hover);cross.classList.add('is-grabbing');mapElement.setPointerCapture?.(h.id);render();status('Vrchol uchopen. Posouvejte mapu prstem; puštěním potvrdíte.');},450);}
 function pointerMove(e){if(!hold||e.pointerId!==hold.id)return;const h=hold;if(!h.grabbed){if(Math.hypot(e.clientX-h.x,e.clientY-h.y)>8)endHold(true);return;}e.preventDefault();e.stopImmediatePropagation();const center=h.center.subtract(L.point(e.clientX-h.x,e.clientY-h.y));map.setView(map.unproject(center,h.zoom),h.zoom,{animate:false});points[selected]=snap(map.getCenter(),true);line();handles.eachLayer(l=>{if(l.options.title==='Vrchol '+(selected+1))l.setLatLng(points[selected]);});}
 mapElement.addEventListener('pointerdown',pointerDown,true);
 mapElement.addEventListener('pointermove',pointerMove,{capture:true,passive:false});
 mapElement.addEventListener('pointerup',e=>{if(hold&&e.pointerId===hold.id){if(hold.grabbed){e.preventDefault();e.stopImmediatePropagation();}endHold(false);}},true);
 mapElement.addEventListener('pointercancel',()=>endHold(true),true);
 mapElement.addEventListener('lostpointercapture',()=>{if(hold?.grabbed)endHold(true);});
 mapElement.addEventListener('contextmenu',e=>{if(hold){e.preventDefault();}});
 mapElement.addEventListener('click',e=>{if(Date.now()<suppressClickUntil&&!e.target.closest('.leaflet-control')){e.preventDefault();e.stopImmediatePropagation();}},true);
 window.addEventListener('blur',()=>endHold(true));
 map.on('move zoomend',highlightTarget);
 function put(ll,append=false){if(blocked())return;const p=snap(L.latLng(ll),true);if(!Number.isFinite(p.lat)||!Number.isFinite(p.lng))return;
 if(points.some((q,i)=>i!==(append?-1:selected)&&q.distanceTo(p)<.001)){status('Tento vrchol už v hranici existuje. Pro uzavření použijte Dokončit hranici.',true);return;}
 if((append||selected<0)&&points.length>=1000){status('Hranice může mít nejvýše 1000 vrcholů.',true);return;}
 record();if(append){const index=selected>=0?selected+1:insertion!==null?insertion:points.length;points.splice(index,0,p);selected=index;insertion=null;}else if(selected>=0)points[selected]=p;else if(insertion!==null){points.splice(insertion,0,p);selected=insertion;insertion=null;}else{points.push(p);selected=points.length-1;}render();}
 function crossClick(){if(blocked())return;const target=targetAtCenter();if(target){if(target.mid){if(points.length>=1000)return;record();points.splice(target.index,0,L.latLng(target.ll));}selected=target.index;insertion=null;render();return;}applyPosition(true);}
 map.on('click',e=>{if(!active||isBusy()||Date.now()<suppressClickUntil)return;if(mode==='mouse')put(e.latlng,true);else if(mode==='cross')crossClick();else applyPosition(true);});
 map.on('mousemove',e=>{if(active&&mode==='mouse')snap(e.latlng,true);});
 map.on('move zoomend',preview);
 $('snapAreas').onchange=preview;
 $('clearVertex').onclick=()=>{if(blocked())return;selected=-1;insertion=null;render();};
 $('removeVertex').onclick=()=>{if(blocked()||selected<0)return;record();points.splice(selected,1);selected=-1;insertion=null;render();};
 $('undoVertex').onclick=()=>{if(blocked()||!undo.length)return;redo.push(state());const s=undo.pop();points=clone(s.points);selected=s.selected;insertion=s.insertion;render();};
 $('redoVertex').onclick=()=>{if(blocked()||!redo.length)return;undo.push(state());const s=redo.pop();points=clone(s.points);selected=s.selected;insertion=s.insertion;render();};
 function stopGNSS(){watchSerial++;if(watch!==null)navigator.geolocation?.clearWatch(watch);watch=null;fix=null;if(map.hasLayer(gnss))map.removeLayer(gnss);$('gnssInfo').textContent='';}
 function startGNSS(){stopGNSS();if(!navigator.geolocation){$('gnssInfo').textContent='Zařízení neposkytuje polohu.';return;}const serial=watchSerial;$('gnssInfo').textContent='Čekám na polohu GNSS…';watch=navigator.geolocation.watchPosition(p=>{if(serial!==watchSerial||!active||mode!=='gnss')return;const first=!fix;fix=p;const ll=L.latLng(p.coords.latitude,p.coords.longitude);gnss.setLatLng(ll).addTo(map);if(first)map.setView(ll,Math.max(18,map.getZoom()));$('gnssInfo').textContent='Hlášená přesnost ±'+Math.round(p.coords.accuracy)+' m. '+($('snapAreas').checked?'Přichytávání je zapnuté.':'Přesná poloha poskytnutá zařízením, bez přichytávání.');controls();preview();highlightTarget();},e=>{if(serial!==watchSerial)return;stopGNSS();controls();$('gnssInfo').textContent=e.code===1?'Povolte polohu pro tento web.':'Polohu se nepodařilo načíst. Přepněte způsob zadání a zkuste GNSS znovu.';},{enableHighAccuracy:true,maximumAge:0,timeout:15000});}
 $('vertexMethod').onchange=()=>{if(blocked())return;endHold(true);mode=$('vertexMethod').value;stopGNSS();if(mode==='gnss')startGNSS();render();};
 function applyPosition(append=false){if(blocked())return;if(mode==='cross'){put(map.getCenter(),append);return;}if(mode!=='gnss')return;if(!fix||Date.now()-fix.timestamp>15000){status('Poloha je starší než 15 sekund. Vyčkejte na nové měření.',true);return;}if(fix.coords.accuracy>5&&!confirm('Hlášená přesnost je ±'+Math.round(fix.coords.accuracy)+' m. Použít tuto polohu?'))return;put([fix.coords.latitude,fix.coords.longitude],append);}
 $('applyVertex').onclick=()=>applyPosition(false);
 function valid(){if(points.length<3)return false;const ps=points.map(p=>map.project(p,18));
  const orient=(a,b,c)=>(b.x-a.x)*(c.y-a.y)-(b.y-a.y)*(c.x-a.x),between=(a,b,c)=>Math.min(a.x,b.x)-1e-7<=c.x&&c.x<=Math.max(a.x,b.x)+1e-7&&Math.min(a.y,b.y)-1e-7<=c.y&&c.y<=Math.max(a.y,b.y)+1e-7;
  for(let i=0;i<ps.length;i++)for(let j=i+1;j<ps.length;j++){if(j===i+1||i===0&&j===ps.length-1)continue;const a=ps[i],b=ps[(i+1)%ps.length],c=ps[j],d=ps[(j+1)%ps.length],o1=orient(a,b,c),o2=orient(a,b,d),o3=orient(c,d,a),o4=orient(c,d,b);if(o1*o2<0&&o3*o4<0||Math.abs(o1)<1e-7&&between(a,b,c)||Math.abs(o2)<1e-7&&between(a,b,d)||Math.abs(o3)<1e-7&&between(c,d,a)||Math.abs(o4)<1e-7&&between(c,d,b))return false;}
  return Math.abs(ps.reduce((sum,p,i)=>{const q=ps[(i+1)%ps.length];return sum+p.x*q.y-q.x*p.y;},0))>1e-7;
 }
 function clear(){endHold(true);active=false;points=[];undo=[];redo=[];selected=-1;insertion=null;group.clearLayers();handles.clearLayers();cross.hidden=true;cross.classList.remove('can-grab');if(map.hasLayer(hover))map.removeLayer(hover);stopGNSS();snap(null,true);controls();onActive(false);}
 function finish(){if(blocked())return false;if(!valid()){status('Hranice nebyla potvrzena. Musí mít alespoň tři různé vrcholy, nenulovou výměru a nesmí se křížit ani dotýkat sama sebe. Plochu nelze uložit, dokud hranici neopravíte a nedokončíte.',true);return false;}const ring=points.map(p=>[p.lng,p.lat]);ring.push([...ring[0]]);clear();onComplete({type:'Polygon',coordinates:[ring]});return true;}
 $('finishVertices').onclick=finish;
 $('cancelBoundary').onclick=()=>{if(blocked())return;if(undo.length&&!confirm('Zahodit rozpracované úpravy hranice?'))return;clear();status('Úpravy hranice zrušeny.');};
 const locateControl=L.control({position:'topright'});locateControl.onAdd=()=>{const box=L.DomUtil.create('div','area-map-controls');const locateBox=L.DomUtil.create('div','leaflet-bar',box);const b=L.DomUtil.create('button','map-locate',locateBox);b.type='button';b.textContent='🎯';b.title='Moje poloha';b.setAttribute('aria-label','Moje poloha');L.DomEvent.disableClickPropagation(box);L.DomEvent.disableScrollPropagation(box);b.onclick=()=>{if(!navigator.geolocation){status('Zařízení neposkytuje polohu.',true);return;}b.disabled=true;navigator.geolocation.getCurrentPosition(p=>{b.disabled=false;map.setView([p.coords.latitude,p.coords.longitude],Math.max(18,map.getZoom()));status('Moje poloha · hlášená přesnost ±'+Math.round(p.coords.accuracy)+' m.');},()=>{b.disabled=false;status('Polohu nelze načíst. Zkontrolujte oprávnění tohoto webu.',true);},{enableHighAccuracy:true,timeout:15000,maximumAge:0});};return box;};locateControl.addTo(map);
 const below=$('boundaryBelow');below.append(document.querySelector('.boundary-start'),$('boundaryTools'),document.querySelector('.snap-option'));
 $('vertexMethod').closest('label').hidden=true;
 const methods=L.control({position:'bottomleft'});methods.onAdd=()=>{const bar=L.DomUtil.create('div','vertex-mode-bar');bar.id='vertexModeBar';bar.setAttribute('role','group');bar.setAttribute('aria-label','Zadávání vrcholů');L.DomEvent.disableClickPropagation(bar);L.DomEvent.disableScrollPropagation(bar);
 for(const [value,label,title] of [['mouse','↖ Klik','Kliknutí a tažení v mapě'],['cross','＋ Kříž','Zadávání pod křížem'],['gnss','⌖ GNSS','Zadávání aktuální polohy GNSS']]){const b=L.DomUtil.create('button','',bar);b.type='button';b.textContent=label;b.title=title;b.dataset.vertexMode=value;b.onclick=()=>{if(blocked())return;$('vertexMethod').value=value;$('vertexMethod').dispatchEvent(new Event('change'));};}
 bar.append($('undoVertex'),$('redoVertex'));return bar;};methods.addTo(map);controls();
 window.addEventListener('pagehide',stopGNSS);
 return {getBounds(){return L.latLngBounds(points);},get active(){return active;},start(geometry){if(!canEdit||isBusy()||active)return;points=geometry?geometry.coordinates[0].slice(0,-1).map(p=>L.latLng(p[1],p[0])):[];undo=[];redo=[];selected=-1;insertion=null;active=true;mode=$('vertexMethod').value;onActive(true);if(mode==='gnss')startGNSS();render();},reset:clear,finish};
};
