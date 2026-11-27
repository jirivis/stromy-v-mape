'use strict';
// Read-only project context. Never applies table filters or changes the edited geometry.
window.SVMContextMap = {
 panes(map) {
  for (const [name, z] of [['contextTrees', 390], ['contextAreas', 420]]) {
   const pane = map.getPane(name) || map.createPane(name);
   pane.style.zIndex = z;
   pane.style.pointerEvents = 'none';
  }
 },
 async data(url, projectId) {
  const response = await fetch(url, {credentials:'same-origin', cache:'no-store'});
  if (!response.ok) throw Error('Okolní objekty se nepodařilo načíst.');
  const data = await response.json();
  if (Number(data.project_id) !== Number(projectId)) throw Error('Aktivní projekt se změnil. Obnovte stránku.');
  return data;
 },
 async trees(map, projectId, excludedId) {
  const data = await this.data('/map/public/project_tools/api.php?action=rows&kind=trees', projectId);
  const group = L.featureGroup().addTo(map);
  for (const row of data.rows) {
   if (Number(row.id) === Number(excludedId) || row.geometry?.type !== 'Point') continue;
   const [lng, lat] = row.geometry.coordinates;
   if (!Number.isFinite(lat) || !Number.isFinite(lng)) continue;
   const icon = (row.values.status === 'removed' || Number(row.values.vitality) === 5 || row.values.removal_type) ? 'stump' : row.values.angio_gymno === 'gymnospermae' ? 'gymno' : 'angio';
   L.marker([lat,lng], {pane:'contextTrees', interactive:false, keyboard:false,
    icon:L.divIcon({className:'context-tree', iconSize:[20,20], iconAnchor:[10,10],
     html:'<img alt="" src="/icons/'+icon+'.svg" style="width:20px;height:20px;background:#fff;border:1px solid #445e4b;border-radius:50%;box-sizing:border-box">'})
   }).addTo(group);
  }
  return group;
 },
 async areas(map, projectId, excludedId) {
  const data = await this.data('/map/public/areas/api.php?action=list', projectId);
  return L.geoJSON(data.features.filter(f => Number(f.properties.id) !== Number(excludedId)), {
   pane:'contextAreas', interactive:false,
   style:f=>({color:f.properties.attributes.status === 'removed' ? '#a94137' : '#718574', weight:1.5, fillOpacity:.12})
  }).addTo(map);
 },
 focusControl(map, getBounds, title, status=()=>{}) {
  const control = L.control({position:'topright'});
  control.onAdd = () => {
   const box = L.DomUtil.create('div','leaflet-bar');
   const button = L.DomUtil.create('button','',box);
   button.type='button';button.textContent='⛶';button.title=title;button.setAttribute('aria-label',title);
   button.style.cssText='width:34px;height:34px;min-height:34px;padding:0;background:white;color:#244b38;border:0;border-radius:0;font-size:24px;cursor:pointer';
   L.DomEvent.disableClickPropagation(box);L.DomEvent.disableScrollPropagation(box);
   button.onclick=()=>{const bounds=getBounds();if(bounds?.isValid())map.fitBounds(bounds,{padding:[25,25],maxZoom:20});else status('Nejdříve zakreslete hranici plochy.');};
   return box;
  };
  return control.addTo(map);
 }
};
