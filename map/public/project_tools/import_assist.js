'use strict';
const SvmImportAssist=(()=>{
 const norm=s=>String(s).normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]/g,'');
 const aliases={usr_id:['cislo','cislo stromu','tree number','treenumber','tree_no'],code:['cislo plochy','area number'],species:['latin','latinsky','scientific name','botanical name'],czech:['cesky','cesky nazev','common name'],spread_m:['spread','crown width','crown diameter','sirka koruny'],height:['vyska','tree height','vyska stromu'],canopy_h_m:['nasazeni koruny','crown base height','vyska nasazeni koruny'],longevity:['perspektiva'],stability:['stabilita'],vitality:['vitalita'],tree_health:['zdrav stav','zdravotni stav'],phy_age:['fyziologicke stari'],last_insp_at:['last insp','posledni kontrola'],measured_at:['datum mereni'],planted:['datum vysadby'],next_insp_at:['dalsi kontrola'],angio_gymno:['nahokryto','naho kryto'],note_tree:['poznamka ke stromu'],lat_wgs84:['latitude','lat'],lng_wgs84:['longitude','lon','lng'],y_sjtsk:['y','jtsk y'],x_sjtsk:['x','jtsk x']};
 for(let i=1;i<=4;i++){aliases['dbh'+i+'_cm']=['dbh'+i,'prumer kmene '+i];aliases['girth'+i+'_cm']=['girth'+i,'obvod kmene '+i];}
 aliases.dbh1_cm.push('dbh','prumer kmene');aliases.girth1_cm.push('girth','obvod kmene');
 function guess(header,options,kind='trees'){
  const h=norm(header),available=new Set(options.map(o=>o.value));if(['id','fid','projectid'].includes(h))return '';
  const direct=options.filter(o=>o.value&&norm(o.value)===h);if(direct.length===1)return direct[0].value;
  const hits=[];for(const [key,names] of Object.entries(aliases))if(available.has(key)&&names.some(n=>norm(n)===h))hits.push(key);
  if(kind==='areas'&&['cislo','areanumber'].includes(h)&&available.has('code'))return 'code';
  if(hits.length===1)return hits[0];
  const labels=options.filter(o=>o.value&&norm(o.textContent.replace(/\s*\([^)]*\)\s*$/,''))===h);return labels.length===1?labels[0].value:'';
 }
 function suggest(container,headers,kind='trees'){
  const selects=[...container.querySelectorAll('select')],used=new Set();
  // Preserve existing explicit matches first; never propose a target twice.
  for(const s of selects){if(s.value&&used.has(s.value))s.value='';else if(s.value)used.add(s.value);}
  selects.forEach((s,i)=>{if(s.value)return;const key=guess(headers[i],[...s.options],kind);if(key&&!used.has(key)){s.value=key;used.add(key);s.title='Navržené přiřazení – před importem zkontrolujte.';}});
 }
 function success(button,count,mode,kind){
  document.getElementById('importSuccess')?.remove();const box=document.createElement('section');box.id='importSuccess';box.tabIndex=-1;box.setAttribute('role','status');box.style.cssText='margin:20px 0;padding:20px;border:2px solid #2d743e;border-radius:8px;background:#edf8ef';
  const heading=document.createElement('h2');heading.textContent='✓ Import byl úspěšně dokončen';const p=document.createElement('p');p.textContent=(mode==='update'?'Aktualizované':'Přidané')+' '+(kind==='areas'?'plochy':'stromy')+': '+count+'. Změny jsou uložené v projektu.';box.append(heading,p);
  for(const [label,url] of [['Otevřít tabulku projektu','table.php'],['Otevřít mapu','../'],['Další import',location.pathname]]){const a=document.createElement('a');a.textContent=label;a.href=url;a.style.marginRight='20px';box.append(a);}button.after(box);box.focus();box.scrollIntoView({block:'center',behavior:'smooth'});
 }
 return {guess,suggest,success};
})();
if(typeof module!=='undefined')module.exports=SvmImportAssist;

if(typeof window!=='undefined')window.SvmImportAssist=SvmImportAssist;
