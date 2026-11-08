'use strict';
window.SvmImportNames=(()=>{
 const normalize=v=>String(v??'').trim().replace(/\s+/g,' ').toLocaleLowerCase('cs');
 function candidates(catalog,source,value,target){const from=source==='species'?'scientific_name':'czech_name',to=target==='species'?'scientific_name':'czech_name';return [...new Set(catalog.filter(r=>normalize(r[from])===normalize(value)&&normalize(r[to])).map(r=>String(r[to]).trim()))];}
 function attach({holder,getRows,invalidate,controls}){
  const box=document.createElement('section');box.hidden=true;box.style.margin='16px 0';holder.after(box);let overrides=[],generation=0,catalog=null;
  const heading=document.createElement('h3');heading.textContent='Doplnit chybějící názvy';box.append(heading);const info=document.createElement('p');info.textContent='Doplní pouze prázdné názvy podle číselníku. Výsledky zkontrolujte v náhledu; nejednoznačné shody vyberte ručně.';box.append(info);
  const result=document.createElement('div'),buttons=[];
  for(const [target,label] of [['czech','Doplnit české názvy'],['species','Doplnit latinské názvy']]){const b=document.createElement('button');b.type='button';b.textContent=label;b.style.marginRight='10px';buttons.push(b);box.append(b);b.onclick=()=>fill(target);}
  box.append(result);
  function clear(){generation++;overrides=[];result.replaceChildren();box.hidden=!getRows();buttons.forEach(b=>b.disabled=false);}
  function apply(row,i){const out={...row};for(const [k,v] of Object.entries(overrides[i]||{}))if(!normalize(out[k]))out[k]=v;return out;}
  function metadata(){return overrides.map(v=>Object.keys(v||{}));}
  async function fill(target){const rows=getRows();if(!rows)return;const serial=++generation;buttons.forEach(b=>b.disabled=true);result.textContent='Načítám číselník…';
   try{if(!catalog){const r=await fetch('import_names.php',{cache:'no-store'});const d=await r.json();if(!r.ok||d.error)throw Error(d.error||'Číselník není dostupný.');if(d.project_id!==ProjectTools.project_id)throw Error('Aktivní projekt se změnil. Obnovte stránku.');catalog=d.names;}
    if(serial!==generation)return;invalidate();result.replaceChildren();const source=target==='czech'?'species':'czech';let filled=0,missing=0,ambiguous=0;
    rows.forEach((row,i)=>{if(normalize(apply(row,i)[target])||!normalize(row[source]))return;const hits=candidates(catalog,source,row[source],target);if(hits.length===1){(overrides[i]??={})[target]=hits[0];filled++;}else if(hits.length===0)missing++;else{ambiguous++;const label=document.createElement('label');label.textContent='Řádek '+(i+1)+' · '+row[source]+' ';const sel=document.createElement('select');sel.append(new Option('Nedoplňovat',''));hits.forEach(v=>sel.append(new Option(v,v)));sel.onchange=()=>{invalidate();overrides[i]??={};if(sel.value)overrides[i][target]=sel.value;else delete overrides[i][target];};label.append(sel);result.append(label);}});
    const p=document.createElement('p');p.textContent='Doplněno: '+filled+' · nenalezeno: '+missing+' · k výběru: '+ambiguous+'. Připravte nový kontrolní náhled.';result.prepend(p);
   }catch(e){if(serial===generation)result.textContent=e.message+' Běžný import zůstává dostupný.';}finally{if(serial===generation)buttons.forEach(b=>b.disabled=false);}
  }
  controls.addEventListener('change',e=>{if(!box.contains(e.target))clear();});
  return {clear,apply,metadata};
 }
 return {attach,candidates};
})();
