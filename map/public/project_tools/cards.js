'use strict';
(async()=>{
 const $=id=>document.getElementById(id);let selection,busy=false,complete=false;
 const pause=ms=>new Promise(r=>setTimeout(r,ms));
 function source(c){return new URL(c.kind==='trees'?'../karta.php?id='+encodeURIComponent(c.id):'area_card.php?id='+encodeURIComponent(c.id),location.href).href+(!$('withPhotos').checked?'&without_photos=1':'');}
 async function waitImages(root){const images=[...root.querySelectorAll('img')];images.forEach(i=>i.loading='eager');await Promise.all(images.map(i=>new Promise(resolve=>{if(i.complete)return resolve();i.addEventListener('load',resolve,{once:true});i.addEventListener('error',resolve,{once:true});setTimeout(resolve,15000);})));return images.filter(i=>!i.complete||!i.naturalWidth).length;}
 async function canonical(c){
  const iframe=document.createElement('iframe');iframe.className='card-source';iframe.title='Načítání karty';iframe.setAttribute('aria-hidden','true');iframe.tabIndex=-1;
  try{
   const response=await fetch(source(c),{cache:'no-store',signal:AbortSignal.timeout(45000)});
   if(!response.ok)throw Error('Kartu '+c.code+' nelze načíst (HTTP '+response.status+').');
   if(new URL(response.url).pathname!==new URL(source(c)).pathname)throw Error('Přihlášení vypršelo. Přihlaste se znovu.');
   const html=new DOMParser().parseFromString(await response.text(),'text/html');
   const base=html.createElement('base');base.href=source(c);html.head.prepend(base);
   if(c.kind==='trees')html.querySelectorAll('script').forEach(n=>n.remove());
   if(!$('withPhotos').checked)html.querySelectorAll('.gallery,#cardPhotos').forEach(n=>{if(c.kind==='trees')(n.closest('section')||n).remove();});
   iframe.srcdoc='<!doctype html>'+html.documentElement.outerHTML;document.body.append(iframe);
   const startedLoad=Date.now();while(true){const d=iframe.contentDocument;if(d&&d.querySelector(c.kind==='trees'?'.card-page':'.porost-page')&&d.readyState!=='loading')break;if(Date.now()-startedLoad>20000)throw Error('Karta '+c.code+' se nedokončila. Otevřete ji samostatně.');await pause(100);}
   const doc=iframe.contentDocument;
   if(c.kind==='areas'){
    const started=Date.now();while(!iframe.contentWindow.AreaCardReady){if(iframe.contentWindow.AreaCardError)throw Error(iframe.contentWindow.AreaCardError);if(Date.now()-started>45000)throw Error('Karta porostu se nedokončila.');await pause(100);}
   }
   const main=doc.querySelector(c.kind==='trees'?'.card-page':'.porost-page');if(!main)throw Error('Skutečná karta objektu '+c.code+' není dostupná.');
   if(!$('withPhotos').checked){main.querySelectorAll('.gallery,#cardPhotos').forEach(n=>(n.closest('section')||n).remove());}
   const failed=await waitImages(main);await doc.fonts?.ready;
   const host=document.createElement('article');host.className='canonical-card';const shadow=host.attachShadow({mode:'open'});
   // Keep each original stylesheet isolated. Tree and area cards must not override one another.
   for(const node of doc.querySelectorAll('style,link[rel="stylesheet"]')){
    let css=node.textContent;if(node.tagName==='LINK'){const r=await fetch(node.href);if(!r.ok)throw Error('Styl karty se nepodařilo načíst.');css=await r.text();css=css.replace(/url\((['"]?)([^)'"\s]+)\1\)/g,(all,q,url)=>url.startsWith('#')||url.startsWith('data:')?all:'url("'+new URL(url,node.href).href+'")');}
    const style=document.createElement('style');style.textContent=css.replace(/:root\b/g,':host').replace(/\bhtml\b(?=[\s,{.#:\[])/g,':host').replace(/\bbody\b(?=[\s,{.#:\[])/g,'.canonical-body');shadow.append(style);
   }
   const wrap=document.createElement('div');wrap.className='canonical-body';const clone=main.cloneNode(true);
   clone.querySelectorAll('script,.controls,.tools,#status,.leaflet-control-zoom,.leaflet-control-layers').forEach(n=>n.remove());
   for(const el of clone.querySelectorAll('*')){for(const attr of [...el.attributes])if(attr.name.startsWith('on'))el.removeAttribute(attr.name);for(const attr of ['src','href'])if(el.hasAttribute(attr)){const value=el.getAttribute(attr);if(value&&!value.startsWith('#'))el.setAttribute(attr,new URL(value,doc.baseURI).href);}if(el.tagName==='IMG')el.loading='eager';}
   // Leaflet fades loaded tiles in with JavaScript. A static print copy must keep them visible.
   clone.querySelectorAll('.leaflet-tile').forEach(img=>{img.style.opacity='1';img.style.visibility='visible';});
   wrap.append(clone);shadow.append(wrap);const adjust=document.createElement('style');adjust.textContent=':host{display:block;font:14px Arial,sans-serif;color:#1e2c23}.canonical-body{margin:0!important;background:white!important}.controls,.tools{display:none!important}@media print{.card-page,.porost-page{width:auto!important;margin:0!important;padding:0!important;box-shadow:none!important}#cardMap{break-inside:avoid;max-width:100%}*{print-color-adjust:exact;-webkit-print-color-adjust:exact}}';shadow.append(adjust);
   const actions=document.createElement('div');actions.className='card-actions no-print';const open=document.createElement('a');open.href=source(c);open.target='_blank';open.rel='noopener';open.textContent='Otevřít kartu '+c.code;const print=document.createElement('button');print.textContent='Tisk / PDF této karty';print.onclick=()=>printCards(host);actions.append(open,print);$('cardsRoot').append(actions,host);await waitImages(shadow);return failed;
  }finally{iframe.remove();}
 }
 async function prepare(){if(busy)return;busy=true;complete=false;$('printAll').disabled=true;$('withPhotos').disabled=$('reloadCards').disabled=true;$('cardsRoot').replaceChildren();try{
  const d=await PT.api('cards',{selection});if(!d.cards?.length)throw Error('Ve výběru nejsou dostupné objekty.');if($('withPhotos').checked&&d.cards.length>10)throw Error('S fotografiemi vyberte nejvýše 10 objektů.');let failed=0;
  for(let i=0;i<d.cards.length;i++){PT.status('Načítám skutečné karty: '+(i+1)+' / '+d.cards.length+'…');failed+=await canonical(d.cards[i]);}
  complete=true;$('printAll').disabled=false;PT.status('Připraveno karet: '+d.cards.length+'.'+(failed?' Některé obrázky se nenačetly ('+failed+'). Před tiskem zkontrolujte karty.':''));
 }catch(e){PT.status(e.message+' Již připravené karty zůstávají dostupné.',true);complete=$('cardsRoot').querySelector('.canonical-card')!==null;$('printAll').disabled=!complete;}finally{busy=false;$('withPhotos').disabled=$('reloadCards').disabled=false;}}
 async function printCards(one){if(!complete||busy)return;document.body.classList.toggle('print-one',!!one);one?.classList.add('printing');try{await document.fonts?.ready;window.print();}finally{document.body.classList.remove('print-one');one?.classList.remove('printing');}}
 $('printAll').onclick=()=>printCards(null);$('reloadCards').onclick=prepare;$('withPhotos').onchange=()=>{complete=false;$('printAll').disabled=true;$('cardsRoot').replaceChildren();PT.status('Nastavení se změnilo. Klikněte na Připravit karty.');};
 try{const areaId=new URLSearchParams(location.search).get('area');selection=areaId&&/^[1-9][0-9]*$/.test(areaId)?{trees:[],areas:[areaId]}:JSON.parse(sessionStorage.getItem('svm-cards:'+ProjectTools.project_id));if(!selection)throw Error('Nejprve vyberte objekty v tabulce.');PT.status('Výběr je připraven. Klikněte na Připravit karty.');}catch(e){PT.status(e.message,true);}
})();
