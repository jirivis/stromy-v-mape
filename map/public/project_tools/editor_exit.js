'use strict';
(()=>{
 const form=document.querySelector('form[action="tree_save.php"]');if(!form)return;
 let dirty=false,saving=false,uploading=false,leaving=false;
 form.addEventListener('input',e=>{if(e.target.type!=='file'&&!e.target.closest('#tree-photos'))dirty=true;});form.addEventListener('change',e=>{if(e.target.type!=='file'&&!e.target.closest('#tree-photos'))dirty=true;});
 form.addEventListener('click',e=>{if(e.target.closest('#confirmManualLocation'))dirty=true;});
 const close=()=>{if(saving||uploading){alert('Právě probíhá ukládání. Vyčkejte na dokončení.');return;}if((dirty||window.TreePhotosDirty?.())&&!confirm('Zahodit neuložené změny a zavřít formulář?'))return;leaving=true;location.href='/map/public/';};
 const bar=document.createElement('div');bar.className='editor-close-bar';const button=document.createElement('button');button.type='button';button.textContent='Zavřít · Esc';button.onclick=close;bar.append(button);form.before(bar);const bottom=bar.cloneNode(true);bottom.classList.add('editor-close-bottom');bottom.querySelector('button').onclick=close;(document.querySelector('.tree-danger-zone')||document.getElementById('tree-history')||form).after(bottom);
 document.addEventListener('keydown',e=>{if(e.key!=='Escape'||document.querySelector('dialog[open]'))return;e.preventDefault();e.stopImmediatePropagation();close();},true);
 window.addEventListener('beforeunload',e=>{if((dirty||uploading||window.TreePhotosDirty?.())&&!leaving){e.preventDefault();e.returnValue='';}});
 window.TreeFormLifecycle={uploading(value){uploading=value;},saving(){saving=true;},saved(){saving=false;dirty=false;},failed(){saving=false;}};
})();
