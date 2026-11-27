'use strict';
(function(){
const init=TreeApp.initMap;
TreeApp.initMap=function(...args){
 const result=init.apply(this,args),map=TreeApp.map,u=window.TreeAppUser||{};
 if(!['owner','admin','editor'].includes(u.projectRole)&&Number(u.isSuperAdmin)!==1)return result;
 const storageKey='svm-edit-mode:'+u.userId+':'+u.projectId;let enabled=false;try{enabled=localStorage.getItem(storageKey)==='1';}catch{}
 const dialog=document.createElement('dialog');dialog.className='svm-edit-choice';
 const title=document.createElement('h2');title.textContent='Co chcete upravit?';dialog.append(title);title.id='svm-edit-choice-title';dialog.setAttribute('aria-labelledby',title.id);
 const choices=document.createElement('div'),cancel=document.createElement('button');cancel.textContent='Zrušit';cancel.type='button';cancel.onclick=()=>dialog.close();dialog.append(choices,cancel);document.body.append(dialog);
 const control=L.control({position:'topleft'});control.onAdd=()=>{const b=L.DomUtil.create('button','leaflet-bar svm-edit-toggle');b.type='button';b.textContent=enabled?'Editace ✓':'Editace';b.title=enabled?'Editace zapnutá – klikněte na objekt':'Zapnout úpravy kliknutím na objekt';b.setAttribute('aria-pressed',String(enabled));b.classList.toggle('is-active',enabled);map.getContainer().classList.toggle('svm-editing',enabled);L.DomEvent.disableClickPropagation(b);b.onclick=e=>{e.stopPropagation();enabled=!enabled;try{localStorage.setItem(storageKey,enabled?'1':'0');}catch{}b.setAttribute('aria-pressed',String(enabled));b.classList.toggle('is-active',enabled);b.textContent=enabled?'Editace ✓':'Editace';b.title=enabled?'Editace zapnutá – klikněte na objekt':'Zapnout úpravy kliknutím na objekt';map.getContainer().classList.toggle('svm-editing',enabled);map.closePopup();};return b;};control.addTo(map);
 const areaButton=map.getContainer().querySelector('.svm-add-area');if(areaButton)areaButton.after(control.getContainer());
 function insideRing(point,ring){let inside=false;for(let i=0,j=ring.length-1;i<ring.length;j=i++){const a=ring[i],b=ring[j];if((a[1]>point[1])!==(b[1]>point[1])&&point[0]<(b[0]-a[0])*(point[1]-a[1])/(b[1]-a[1])+a[0])inside=!inside;}return inside;}
 function contains(g,ll){const polys=g?.type==='Polygon'?[g.coordinates]:g?.type==='MultiPolygon'?g.coordinates:[];const p=map.latLngToContainerPoint(ll);return polys.some(rings=>{const boundary=rings.some(r=>r.some((v,i)=>{if(!i)return false;const a=map.latLngToContainerPoint([r[i-1][1],r[i-1][0]]),b=map.latLngToContainerPoint([v[1],v[0]]),dx=b.x-a.x,dy=b.y-a.y,den=dx*dx+dy*dy,t=den?Math.max(0,Math.min(1,((p.x-a.x)*dx+(p.y-a.y)*dy)/den)):0;return p.distanceTo(L.point(a.x+t*dx,a.y+t*dy))<=4;}));return boundary||(insideRing([ll.lng,ll.lat],rings[0])&&!rings.slice(1).some(r=>insideRing([ll.lng,ll.lat],r)));});}
 function candidates(ll){const hits=[],p=map.latLngToContainerPoint(ll);let areaPoint=ll;
 for(const [id,marker] of Object.entries(TreeApp.markersById||{})){if(!map.hasLayer(marker))continue;const distance=p.distanceTo(map.latLngToContainerPoint(marker.getLatLng()));if(distance<=Math.max(12,(marker._treeMarkerSize||20)/2)){const t=TreeApp.treesById?.[id]||{};hits.push({type:'tree',id,label:'Strom '+(t.usr_id||'bez čísla')+(t.czech||t.species?' – '+(t.czech||t.species):''),distance});}}
 hits.sort((a,b)=>a.distance-b.distance);if(hits.length)areaPoint=TreeApp.markersById[hits[0].id].getLatLng();
 if(TreeApp.areaLayers&&map.hasLayer(TreeApp.areaLayers))TreeApp.areaLayers.eachLayer(l=>{if(contains(l.feature?.geometry,areaPoint)||contains(l.feature?.geometry,ll)){const p=l.feature.properties;hits.push({type:'area',id:p.id,label:'Plocha '+p.code+(p.attributes?.name?' – '+p.attributes.name:'')});}});
 return hits;
 }
 function open(item){location.href=item.type==='tree'?'/map/public/tree_new.php?id='+encodeURIComponent(item.id):'/map/public/areas/?id='+encodeURIComponent(item.id);}
 const click=e=>{if(!enabled||!(e.target instanceof Element))return;const hitMarker=Object.values(TreeApp.markersById||{}).find(m=>m.getElement()?.contains(e.target));if(e.target.closest('.leaflet-control,.leaflet-popup,.marker-cluster,dialog')||(!hitMarker&&e.target.closest('a,button,input,select,textarea,[role=button]')))return;const hits=candidates(hitMarker?hitMarker.getLatLng():map.mouseEventToLatLng(e));if(!hits.length)return;e.preventDefault();e.stopImmediatePropagation();map.closePopup();if(hits.length===1){open(hits[0]);return;}choices.replaceChildren();for(const hit of hits){const b=document.createElement('button');b.type='button';b.textContent=hit.label;b.onclick=()=>{dialog.close();open(hit);};choices.append(b);}dialog.showModal();};
 map.getContainer().addEventListener('click',click,true);
 map.once('unload',()=>{map.getContainer().removeEventListener('click',click,true);dialog.remove();});
 return result;
};
})();

