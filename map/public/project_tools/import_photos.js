'use strict';
const SvmPhotoImport=(()=>{
 function csv(text){text=text.replace(/^\uFEFF/,'');const first=text.split(/\r?\n/)[0],sep=first.includes(';')?';':first.includes('\t')?'\t':',';let rows=[],row=[],cell='',quoted=false;
  for(let i=0;i<text.length;i++){const c=text[i];if(c==='"'){if(quoted&&text[i+1]==='"'){cell+='"';i++;}else quoted=!quoted;}else if(c===sep&&!quoted){row.push(cell);cell='';}else if((c==='\n'||c==='\r')&&!quoted){if(c==='\r'&&text[i+1]==='\n')i++;row.push(cell);if(row.some(v=>v.trim()))rows.push(row);row=[];cell='';}else cell+=c;}if(quoted)throw Error('Neuzavřené uvozovky v CSV.');row.push(cell);if(row.some(v=>v.trim()))rows.push(row);return rows;
 }
 const path=f=>(f.webkitRelativePath||f.name).replace(/\\/g,'/');
 function assignments(files,mode,kind,code,table,tolerant=false){
  const images=files.filter(f=>/\.(jpe?g|png|webp)$/i.test(f.name));if(!images.length||images.length>500)throw Error('Vyberte 1 až 500 fotografií.');
  if(mode==='csv'){
   const rows=csv(table),head=(rows.shift()||[]).map(v=>v.trim().toLowerCase());const indexes=['soubor','cislo','popisek'].map(v=>head.indexOf(v));if(indexes[0]<0||indexes[1]<0)throw Error('CSV musí mít sloupce soubor a cislo; popisek je volitelný.');
   const used=new Set();const result=rows.map(row=>{row=row.map(v=>/^'(?:[\s]*[=+@-]|[\t\r\n'])/.test(v)?v.slice(1):v);const name=(row[indexes[0]]||'').trim().replace(/\\/g,'/');if(!name)throw Error('Chybí název souboru.');let hits=images.filter(f=>path(f)===name);if(!hits.length)hits=images.filter(f=>path(f).endsWith('/'+name)||f.name===name);if(hits.length!==1)throw Error('Soubor nenalezen nebo název není jednoznačný: '+name);const file=hits[0];if(used.has(path(file)))throw Error('Fotografie je uvedena vícekrát: '+name);used.add(path(file));return {file,code:(row[indexes[1]]||'').trim(),note:indexes[2]>=0?row[indexes[2]]||'':''};});if(!result.length||result.length>500)throw Error('CSV musí obsahovat 1 až 500 řádků.');return result;
  }
  return images.map(file=>{let value=code.trim();if(mode==='folders'){const dirs=path(file).split('/').slice(0,-1).reverse();value='';for(const d of dirs){const m=(kind==='trees'?/^(?:strom[ _-]*)?(\d+)$/i:/^(?:plocha[ _-]*)?(\d+|P\d+)$/i).exec(d);if(m){value=m[1];break;}}}if(!value&&!tolerant)throw Error('Nelze určit číslo objektu: '+path(file));if(kind==='trees'&&/^\d+$/.test(value))value=String(Number(value));return {file,code:value,note:'',error:value?'':'Nelze určit číslo objektu'};});
 }
 return {csv,path,assignments};
})();
if(typeof module!=='undefined')module.exports=SvmPhotoImport;
if(typeof document!=='undefined')(()=>{
 const $=id=>document.getElementById(id);let files=[],items=[],token=null,busy=false;
 const say=t=>$('photoStatus').textContent=t;
 function invalidate(){token=null;$('photoCommit').disabled=true;$('photoRows').replaceChildren();}
 $('photoFolder').onchange=e=>{files=[...e.target.files];$('photoFiles').value='';invalidate();say('Vybráno souborů: '+files.length);};$('photoFiles').onchange=e=>{files=[...e.target.files];$('photoFolder').value='';invalidate();say('Vybráno souborů: '+files.length);};for(const id of ['photoKind','photoMode','photoCode','photoCsv'])$(id).onchange=invalidate;
 async function api(action,body){const r=await fetch('import_photos_api.php?action='+action,{method:'POST',headers:{'X-CSRF-Token':ProjectTools.csrf,'X-Project-Id':String(ProjectTools.project_id),...(body instanceof FormData?{}:{'Content-Type':'application/json'})},body:body instanceof FormData?body:JSON.stringify(body)});let d;try{d=await r.json();}catch{throw Error('Server import přerušil. Zkuste pokračovat; již potvrzené fotografie se nepřidají znovu.');}if(!r.ok)throw Error(d.error||'Import selhal.');return d;}
 function lock(v){busy=v;for(const item of items)if(item.check)item.check.disabled=v||!!item.error||item.done;$('photoInputs').disabled=v;$('photoCommit').disabled=v||!token;}
 $('photoPreview').onclick=async()=>{if(busy)return;invalidate();lock(true);try{
  const csvFile=$('photoCsv').files[0];if(csvFile&&csvFile.size>2*1024*1024)throw Error('CSV může mít nejvýše 2 MB.');
  items=SvmPhotoImport.assignments(files,$('photoMode').value,$('photoKind').value,$('photoCode').value,csvFile?await csvFile.text():'',true);const rows=[];
  for(const item of items){if(item.file.size>5*1024*1024)item.error='Fotografie přesahuje 5 MB';const tr=document.createElement('tr'),td=document.createElement('td'),check=document.createElement('input');check.type='checkbox';check.checked=!item.error;check.disabled=!!item.error;check.setAttribute('aria-label','Importovat '+SvmPhotoImport.path(item.file));td.append(check);tr.append(td);item.check=check;for(const text of [SvmPhotoImport.path(item.file),item.code,item.note,item.error?'Přeskočeno: '+item.error:'Kontroluji…']){const cell=document.createElement('td');cell.textContent=text;tr.append(cell);}item.status=tr.lastChild;item.done=false;$('photoRows').append(tr);}

  for(const [i,item] of items.entries()){if(item.error)continue;item.planIndex=rows.length;say('Kontroluji fotografii '+(i+1)+' / '+items.length);const digest=await crypto.subtle.digest('SHA-256',await item.file.arrayBuffer());rows.push({file:SvmPhotoImport.path(item.file),code:item.code,note:item.note,sha256:[...new Uint8Array(digest)].map(b=>b.toString(16).padStart(2,'0')).join('')});}
  if(!rows.length)throw Error('Žádná fotografie není připravena k importu. Problémové soubory jsou uvedeny v tabulce.');
  const d=await api('preview',{kind:$('photoKind').value,rows});token=d.token;
  for(const item of items)if(!item.error)item.status.textContent='Připraveno';say('Zkontrolujte přiřazení. Připraveno '+rows.length+', přeskočeno '+(items.length-rows.length)+'. Zrušením zaškrtnutí můžete vynechat další fotografie. Zatím nebylo nic nahráno.');
 }catch(e){say(e.message);}finally{lock(false);}};
 $('photoCommit').onclick=async()=>{if(busy||!token)return;lock(true);try{
  for(const [i,item] of items.entries()){if(item.done||!item.check.checked)continue;item.status.textContent='Nahrávám…';const f=new FormData();f.set('plan',token);f.set('index',String(item.planIndex));f.set('photo',item.file);try{await api('upload',f);item.done=true;item.status.textContent='Uloženo';say('Uloženo '+items.filter(x=>x.done).length+' / '+items.length);}catch(e){item.status.textContent=e.message;throw e;}}
  say('Import dokončen. Uloženo '+items.filter(x=>x.done).length+', přeskočeno '+items.filter(x=>!x.done).length+' fotografií.');token=null;
 }catch(e){say('Import pozastaven: '+e.message+' Úspěšně uložené fotografie zůstaly zachované.');$('photoCommit').textContent='Pokračovat v importu';say($('photoStatus').textContent+' Problémovou fotografii můžete odškrtnout a pokračovat.');}finally{lock(false);}};
 window.addEventListener('beforeunload',e=>{if(busy){e.preventDefault();e.returnValue='';}});
})();
