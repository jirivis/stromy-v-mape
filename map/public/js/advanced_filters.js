'use strict';
(function(){
 const app=TreeApp,trees=SVMFilters.create('trees'),areas=SVMFilters.create('areas');
 let catalogError=false;
 function showError(){const content=app.map?.getContainer().querySelector('.tree-filter-control__content');if(catalogError&&content&&!content.querySelector('[data-filter-error]')){const p=document.createElement('p');p.dataset.filterError='1';p.setAttribute('role','alert');p.textContent='Sloupce filtrů se nepodařilo načíst. Obnovte stránku; rychlé filtry jsou dostupné.';content.append(p);}}
 app.projectTreeFilters=trees;app.projectAreaFilters=areas;
 const active=()=>SVMFilters.isActive(trees.state,'trees')||SVMFilters.isActive(areas.state,'areas');
 function indicator(){
  const button=app.map?.getContainer().querySelector('.tree-filter-control__toggle');
  const on=active();
  if(button){button.classList.toggle('has-active-filters',on);button.style.color=on?'#b42323':'';button.style.borderColor=on?'#b42323':'';button.title=on?'Aktivní filtry stromů nebo ploch':'Filtr bodů a ploch';button.setAttribute('aria-label',button.title);}
  if(!on)SVMFilters.notice(false);
 }
 trees.subscribe(indicator);areas.subscribe(indicator);
 window.addEventListener('pageshow',e=>{if(e.persisted){indicator();SVMFilters.notice(active());}});
 app.treeMatchesActiveFilters=tree=>!!tree&&trees.matches(tree.filter_values||tree);
 trees.subscribe(()=>app.applyTreeFilters());
 const init=app.initTreeFilterControl;
 app.initTreeFilterControl=function(...args){
  const result=init.apply(this,args),content=app.map.getContainer().querySelector('.tree-filter-control__content');
  if(content&&!content.dataset.sharedFilters){content.dataset.sharedFilters='1';trees.mount(content);const count=document.createElement('p');count.id='treeFilterCount';content.append(count);app.updateTreeFilterCount();}
  showError();indicator();SVMFilters.notice(active());return result;
 };
 // Only catalog metadata is fetched; map features already carry their filter values.
 fetch('/map/public/project_tools/api.php?action=filter_fields',{credentials:'same-origin',cache:'no-store'})
  .then(r=>{if(!r.ok)throw Error();return r.json();})
  .then(data=>{if(Number(data.project_id)!==Number(window.TreeAppUser.projectId))throw Error();trees.setFields(data.trees);areas.setFields(data.areas);})
  .catch(()=>{catalogError=true;showError();});
})();

