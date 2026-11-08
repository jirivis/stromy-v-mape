'use strict';
(function(){
 const app=TreeApp;let rules=[],mode='all';
 const norm=v=>String(v??'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
 function match(tree,rule,fields){const raw=tree[rule.field],empty=raw===null||raw===undefined||raw==='';if(rule.op==='empty')return empty;if(rule.op==='notempty')return !empty;const meta=fields[rule.field]||{},a=norm(raw),b=norm(rule.value),label=norm(meta.options?.[raw]??raw),numeric=meta.type==='number',x=Number(a.replace(',','.')),y=Number(b.replace(',','.')),eq=numeric&&!empty&&b!==''?Number.isFinite(x)&&Number.isFinite(y)&&x===y:a===b||label===b;
 return ({eq:()=>eq,ne:()=>!eq,contains:()=>label.includes(b),notcontains:()=>!label.includes(b),gt:()=>!empty&&b!==''&&(numeric?Number.isFinite(x)&&Number.isFinite(y)&&x>y:a>b),lt:()=>!empty&&b!==''&&(numeric?Number.isFinite(x)&&Number.isFinite(y)&&x<y:a<b)})[rule.op]?.()??false;}
 let fields={angio_gymno:{label:'Kryto / naho',type:'select',options:{angiospermae:'Krytosemenné',gymnospermae:'Nahosemenné (jehličnany)'}},species:{label:'Latinský název',type:'text'},czech:{label:'Český název',type:'text'},usr_id:{label:'Číslo stromu',type:'number'}};
 const original=app.treeMatchesActiveFilters;
 app.treeMatchesActiveFilters=function(tree){return original.call(this,tree)&&(!rules.length||(mode==='any'?rules.some(r=>match(tree,r,fields)):rules.every(r=>match(tree,r,fields))));};
 const init=app.initTreeFilterControl;
 app.initTreeFilterControl=function(...args){const result=init.apply(this,args),content=app.map.getContainer().querySelector('.tree-filter-control__content');if(!content||content.querySelector('.svm-advanced-filters'))return result;
 const make=(tag,text)=>{const e=document.createElement(tag);if(text)e.textContent=text;return e;},box=make('details');box.className='svm-advanced-filters';box.append(make('summary','Další podmínky – stromy'));
 const quick=make('select');quick.setAttribute('aria-label','Kryto / naho');for(const [v,t] of [['','Všechny skupiny'],['gymnospermae','Nahosemenné (jehličnany)'],['angiospermae','Krytosemenné']])quick.append(new Option(t,v));
 const combine=make('select');combine.setAttribute('aria-label','Kombinovat podmínky');combine.append(new Option('Splnit všechny podmínky','all'),new Option('Splnit alespoň jednu','any'));const rows=make('div'),message=make('p');message.setAttribute('role','status');const add=make('button','Přidat podmínku'),apply=make('button','Použít podmínky'),clear=make('button','Zrušit další podmínky');for(const b of [add,apply,clear])b.type='button';
 function applyRules(){rules=[...rows.children].map(row=>({field:row.children[0].value,op:row.children[1].value,value:row.children[2].value}));mode=combine.value;app.applyTreeFilters();message.textContent=rules.length?'Použito podmínek: '+rules.length+'.':'';}
 function addRow(field='species',op='contains',value=''){const row=make('div');row.className='svm-filter-rule';const key=make('select'),operation=make('select'),input=make('input'),remove=make('button','×');remove.type='button';remove.title='Odebrat podmínku';key.setAttribute('aria-label','Pole podmínky');operation.setAttribute('aria-label','Porovnání');input.setAttribute('aria-label','Hodnota');
 for(const [k,f] of Object.entries(fields))key.append(new Option(f.label||k,k));key.value=field;for(const [v,t] of Object.entries({eq:'je',ne:'není',contains:'obsahuje',notcontains:'neobsahuje',gt:'je větší než',lt:'je menší než',empty:'je prázdné',notempty:'není prázdné'}))operation.append(new Option(t,v));operation.value=op;input.value=value;operation.onchange=()=>input.disabled=['empty','notempty'].includes(operation.value);key.onchange=()=>{input.placeholder=key.value==='angio_gymno'?'gymnospermae / angiospermae':'';};remove.onclick=()=>{row.remove();applyRules();};row.append(key,operation,input,remove);rows.append(row);}
 quick.onchange=()=>{for(const row of [...rows.children])if(row.children[0].value==='angio_gymno')row.remove();if(quick.value)addRow('angio_gymno','eq',quick.value);applyRules();};add.onclick=()=>addRow();apply.onclick=applyRules;clear.onclick=()=>{rows.replaceChildren();quick.value='';combine.value='all';applyRules();};content.querySelector('#resetTreeFilters')?.addEventListener('click',()=>clear.click());
 box.append(quick,combine,rows,add,apply,clear,message);content.append(box);
 fetch('/map/public/project_tools/tree_fields.json',{cache:'no-cache'}).then(r=>{if(!r.ok)throw Error();return r.json();}).then(data=>{fields={...data,...fields};}).catch(()=>{message.textContent='Dostupná jsou základní pole. Úplný seznam se nepodařilo načíst.';});return result;
 };
 // Export pure evaluator for regression checks, without modifying the existing filter state.
 app.matchAdvancedTreeRule=(tree,rule)=>match(tree,rule,fields);
})();
