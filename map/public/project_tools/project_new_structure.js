'use strict';
(()=>{
 const boot=window.NewProjectStructure,$=id=>document.getElementById(id),form=$('structureHidden').form,clone=o=>JSON.parse(JSON.stringify(o));
 window.PT=window.PT||{el:(tag,text)=>{const n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;}};
 if(!boot?.available){form.querySelectorAll('button[type=submit]').forEach(b=>b.disabled=true);return;}
 let hidden=clone(boot.hidden),name=boot.name,kind='trees',changed=false;
 const choices=[{name:'Všechna pole',hidden:{trees:[],areas:[]}},...boot.structures];
 choices.forEach((c,i)=>$('structureSelect').append(new Option(c.name,String(i))));
 function sync(){$('structureHidden').value=JSON.stringify(hidden);$('structureName').value=name;}
 function render(){FormStructureFields.render($('structureFields'),boot.catalog[kind],kind,hidden[kind],(key,checked)=>{hidden[kind]=checked?hidden[kind].filter(k=>k!==key):[...hidden[kind],key];name='Vlastní výběr polí';changed=true;sync();});document.querySelectorAll('[data-kind]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.kind===kind)));sync();}
 document.querySelectorAll('[data-kind]').forEach(b=>b.onclick=()=>{kind=b.dataset.kind;render();});
 $('allFields').onclick=()=>{hidden[kind]=[];name='Vlastní výběr polí';changed=true;render();};$('minimalFields').onclick=()=>{hidden[kind]=Object.keys(boot.catalog[kind]);name='Vlastní výběr polí';changed=true;render();};
 $('loadStructure').onclick=()=>{if(changed&&!confirm('Nahradit rozpracovaný výběr polí?'))return;const c=choices[Number($('structureSelect').value)];hidden=clone(c.hidden);name=c.name;changed=true;render();$('structureStatus').textContent='Výběr načten. Uloží se při vytvoření projektu.';};
 render();form.elements.structure_ready.value='1';form.addEventListener('submit',sync);
})();
