'use strict';
(function(){
 const app=TreeApp,trees=SVMFilters.create('trees'),areas=SVMFilters.create('areas');
 let catalogError=false;
 function showError(){const content=app.map?.getContainer().querySelector('.tree-filter-control__content');if(catalogError&&content&&!content.querySelector('[data-filter-error]')){const p=document.createElement('p');p.dataset.filterError='1';p.setAttribute('role','alert');p.textContent='Sloupce filtrů se nepodařilo načíst. Obnovte stránku; rychlé filtry jsou dostupné.';content.append(p);}}
 app.projectTreeFilters=trees;app.projectAreaFilters=areas;
 app.treeMatchesActiveFilters=tree=>!!tree&&trees.matches(tree.filter_values||tree);
 trees.subscribe(()=>app.applyTreeFilters());
 const init=app.initTreeFilterControl;
 app.initTreeFilterControl=function(...args){
  const result=init.apply(this,args),content=app.map.getContainer().querySelector('.tree-filter-control__content');
  if(content&&!content.dataset.sharedFilters){content.dataset.sharedFilters='1';trees.mount(content);const count=document.createElement('p');count.id='treeFilterCount';content.append(count);app.updateTreeFilterCount();}
  showError();return result;
 };
 // Only catalog metadata is fetched; map features already carry their filter values.
 fetch('/map/public/project_tools/api.php?action=filter_fields',{credentials:'same-origin',cache:'no-store'})
  .then(r=>{if(!r.ok)throw Error();return r.json();})
  .then(data=>{if(Number(data.project_id)!==Number(window.TreeAppUser.projectId))throw Error();trees.setFields(data.trees);areas.setFields(data.areas);})
  .catch(()=>{catalogError=true;showError();});
})();
