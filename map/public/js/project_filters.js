'use strict';
// One evaluator and one user/project/geometry-scoped state for maps and tables.
(function(root){
 const norm=v=>String(v??'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
 const ops={eq:'je',ne:'není',contains:'obsahuje',notcontains:'neobsahuje',gt:'je větší než',lt:'je menší než',empty:'je prázdné',notempty:'není prázdné'};
 const defaults=()=>({query:'',mode:'all',rules:[],urgency:'all',inspection:'all',showRemoved:true,status:'',group:''});
 function clean(s){s=s&&typeof s==='object'?s:{};return {...defaults(),query:String(s.query??'').slice(0,500),mode:s.mode==='any'?'any':'all',rules:(Array.isArray(s.rules)?s.rules:[]).filter(r=>r&&typeof r.field==='string'&&Object.hasOwn(ops,r.op)).slice(0,100).map(r=>({field:r.field.slice(0,100),op:r.op,value:String(r.value??'').slice(0,2000)})),urgency:['all','none','u0','u1','u2','u3'].includes(s.urgency)?s.urgency:'all',inspection:['all','overdue','soon','future','missing'].includes(s.inspection)?s.inspection:'all',showRemoved:s.showRemoved!==false,status:['existing','removed'].includes(s.status)?s.status:'',group:['gymnospermae','angiospermae'].includes(s.group)?s.group:''};}
 function text(v,k){
  if(v==null)return '';
  if(k==='status')return ({active:'Existující',existing:'Existující',removed:'Odstraněný'})[v]||String(v);
  if(k==='species'&&Array.isArray(v))return v.map(s=>(s.czech_name||s.scientific_name||'')+(s.share!=null?' ('+s.share+' %)':'')).join('; ');
  if(k==='components'&&Array.isArray(v)){const names={shrubs_low:'Keře nízké',shrubs_high:'Keře vyšší',climbers:'Liány',young:'Mladý porost',middle:'Střední věk',mature:'Dospělý porost',layered:'Diferencovaný porost'};return v.map(s=>(names[s.type]||s.type)+': '+s.area_m2+' m²').join('; ');}
  return typeof v==='object'?JSON.stringify(v):String(v);
 }
 const empty=v=>v==null||v===''||(Array.isArray(v)&&v.length===0);
 function ruleMatch(row,r,fields){
  if(!Object.hasOwn(fields,r.field))return false;
  const raw=row[r.field],meta=fields[r.field],a=norm(meta.options?.[raw]??text(raw,r.field)),b=norm(r.value),isEmpty=empty(raw);
  if(r.op==='empty')return isEmpty;if(r.op==='notempty')return !isEmpty;
  const numeric=meta.type==='number'||['area_m2','lat_wgs84','lng_wgs84','x_sjtsk','y_sjtsk'].includes(r.field);
  const x=numeric?Number(String(raw??'').replace(',','.')):a,y=numeric?Number(r.value.replace(',','.')):b;
  const valid=!isEmpty&&b!==''&&(!numeric||(Number.isFinite(x)&&Number.isFinite(y)));
  const eq=numeric?valid&&x===y:a===b||norm(raw)===b;
  switch(r.op){case 'eq':return eq;case 'ne':return !eq;case 'contains':return a.includes(b);case 'notcontains':return !a.includes(b);case 'gt':return valid&&x>y;case 'lt':return valid&&x<y;default:return false;}
 }
 function urgency(row){for(let i=0;i<4;i++)if(String(row['treatment_u'+i]??'').trim())return 'u'+i;return 'none';}
 function inspection(row,now=new Date()){
  const raw=String(row.next_insp_at??'').trim(),date=new Date(raw+'T00:00:00');
  if(!raw||Number.isNaN(date.getTime()))return 'missing';
  // Calendar days, unaffected by daylight-saving transitions.
  const day=d=>Date.UTC(d.getFullYear(),d.getMonth(),d.getDate());
  const days=(day(date)-day(now))/86400000;return days<0?'overdue':days<=90?'soon':'future';
 }
 function matches(row,state,fields,kind,now){
  if(kind==='trees'){
   if(!state.showRemoved&&row.status==='removed')return false;
   if(state.urgency!=='all'&&urgency(row)!==state.urgency)return false;
   if(state.inspection!=='all'&&inspection(row,now)!==state.inspection)return false;
   if(state.group&&row.angio_gymno!==state.group)return false;
  }else if(state.status&&(row.status||'existing')!==state.status)return false;
  const q=norm(state.query);if(q&&!Object.keys(fields).some(k=>norm(fields[k].options?.[row[k]]??text(row[k],k)).includes(q)))return false;
  return !state.rules.length||(state.mode==='any'?state.rules.some(r=>ruleMatch(row,r,fields)):state.rules.every(r=>ruleMatch(row,r,fields)));
 }
 function isActive(state,kind){
  return !!(norm(state.query)||state.rules.length||(kind==='trees'
   ? state.urgency!=='all'||state.inspection!=='all'||!state.showRemoved||state.group
   : state.status));
 }
 let noticeTimer,removeTimer;
 function notice(active){
  const doc=root.document;if(!doc)return;
  clearTimeout(noticeTimer);clearTimeout(removeTimer);
  doc.getElementById('svm-active-filter-notice')?.remove();
  if(!active)return;
  if(!doc.getElementById('svm-filter-notice-style')){
   const style=doc.createElement('style');style.id='svm-filter-notice-style';
   style.textContent='#svm-active-filter-notice{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:10000;padding:12px 22px;border-radius:10px;background:#fff;color:#a32424;border:1px solid #a32424;box-shadow:0 3px 16px #0003;font:600 16px/1.4 system-ui;pointer-events:none;opacity:1;transition:opacity .6s ease;max-width:calc(100vw - 48px);box-sizing:border-box}@media(prefers-reduced-motion:reduce){#svm-active-filter-notice{transition:none}}';doc.head.append(style);
  }
  const box=doc.createElement('div');box.id='svm-active-filter-notice';box.setAttribute('role','status');box.setAttribute('aria-live','polite');doc.body.append(box);box.textContent='Aktivní filtry';
  noticeTimer=setTimeout(()=>{box.style.opacity='0';removeTimer=setTimeout(()=>box.remove(),650);},500);
 }
 const controllers=new Map();
 function create(kind,fields={},identity){
  identity=identity||root.ProjectTools||root.TreeAppUser||{};
  const user=Number(identity.user_id??identity.userId)||0,project=Number(identity.project_id??identity.projectId)||0;
  const key='svm-filters:v1:'+user+':'+project+':'+kind+(identity.storage_scope?':'+identity.storage_scope:'');
  const restrict=identity.public===true;
  function sanitize(value){const s=clean(value);if(!restrict)return s;s.rules=s.rules.filter(r=>Object.hasOwn(fields,r.field));s.urgency='all';s.showRemoved=true;if(!fields.next_insp_at)s.inspection='all';if(!fields.angio_gymno)s.group='';if(!fields.status)s.status='';return s;}
  if(controllers.has(key)){const c=controllers.get(key);if(Object.keys(fields).length)c.setFields(fields);return c;}
  let state=defaults(),catalog=fields;const listeners=new Set();let persistent=true;
  try{state=sanitize(JSON.parse(root.localStorage.getItem(key)));}catch{persistent=false;}
  const emit=origin=>listeners.forEach(fn=>fn(origin));
  const c={get state(){return state;},get fields(){return catalog;},get persistent(){return persistent;},
   set(next,origin){state=sanitize(next);try{root.localStorage.setItem(key,JSON.stringify(state));persistent=true;}catch{persistent=false;}emit(origin);},
   setFields(next){catalog=next;emit();},subscribe(fn){listeners.add(fn);return ()=>listeners.delete(fn);},
   matches(row){return matches(row,state,catalog,kind);},
   mount(host){return mount(host,c,kind,restrict);}
  };
  root.addEventListener?.('storage',e=>{if(e.key===key){try{state=sanitize(JSON.parse(e.newValue));emit();}catch{}}});controllers.set(key,c);return c;
 }
 function mount(host,c,kind,restrict=false){
  const el=(tag,label)=>{const e=document.createElement(tag);if(label!=null)e.textContent=label;return e;};
  const box=el('section');box.className='svm-shared-filters';host.replaceChildren(box);
  const heading=el('strong',kind==='trees'?'Filtry stromů':'Filtry ploch'),note=el('p',restrict?'Filtr platí pro veřejnou mapu i tabulku. Nabízí pouze zveřejněné údaje.':'Stejný filtr platí pro mapu i tabulku tohoto projektu. Zaškrtnutý výběr se nemění.');box.append(heading,note);
  const query=el('input');query.type='search';query.placeholder='Hledat ve všech sloupcích';query.setAttribute('aria-label','Hledat ve všech sloupcích');box.append(query);
  const quick={};
  function select(key,label,options){const wrap=el('label',label+' '),s=el('select');s.setAttribute('aria-label',label);for(const [v,t] of options)s.append(new Option(t,v));wrap.append(s);box.append(wrap);quick[key]=s;s.onchange=()=>c.set({...c.state,[key]:s.value},box);}
  if(kind==='trees'){
   if(!restrict)select('urgency','Naléhavost zásahu',[['all','Všechny'],['u0','U0 – ihned'],['u1','U1'],['u2','U2'],['u3','U3'],['none','Bez zásahu']]);
   if(!restrict||c.fields.next_insp_at)select('inspection','Příští kontrola',[['all','Všechny termíny'],['overdue','Po termínu'],['soon','Do 90 dnů'],['future','Za více než 90 dnů'],['missing','Bez data']]);
   if(!restrict||c.fields.angio_gymno)select('group','Kryto / naho',[['','Všechny skupiny'],['gymnospermae','Nahosemenné (jehličnany)'],['angiospermae','Krytosemenné']]);
   if(!restrict){const label=el('label','Zobrazit odstraněné stromy '),check=el('input');check.type='checkbox';label.prepend(check);box.append(label);quick.showRemoved=check;check.onchange=()=>c.set({...c.state,showRemoved:check.checked},box);}
  }else if(!restrict||c.fields.status)select('status','Stav ploch',[['','Všechny stavy'],['existing','Existující'],['removed','Odstraněné']]);
  const details=el('details'),summary=el('summary','Podmínky podle sloupců'),mode=el('select');mode.setAttribute('aria-label','Kombinovat podmínky');mode.append(new Option('Splnit všechny podmínky','all'),new Option('Splnit alespoň jednu','any'));
  const rows=el('div'),add=el('button','Přidat podmínku'),apply=el('button','Použít podmínky'),reset=el('button','Zrušit všechny filtry'),status=el('p');status.setAttribute('role','status');
  for(const b of [add,apply,reset])b.type='button';details.append(summary,mode,rows,add,apply);box.append(details,reset,status);
  function addRow(rule={field:Object.keys(c.fields)[0]||'',op:'contains',value:''}){
   const row=el('div');row.className='svm-filter-rule';const field=el('select'),op=el('select'),value=el('input'),del=el('button','×');del.type='button';del.setAttribute('aria-label','Odebrat podmínku');
   for(const [k,m] of Object.entries(c.fields))field.append(new Option(m.label||k,k));
   if(rule.field&&!Object.hasOwn(c.fields,rule.field))field.append(new Option('Nedostupný sloupec: '+rule.field,rule.field));
   field.value=rule.field;for(const [k,t] of Object.entries(ops))op.append(new Option(t,k));op.value=rule.op;value.value=rule.value;
   field.setAttribute('aria-label','Sloupec filtru');op.setAttribute('aria-label','Porovnání');value.setAttribute('aria-label','Hodnota filtru');
   const hint=()=>{value.disabled=['empty','notempty'].includes(op.value);value.placeholder=Object.values(c.fields[field.value]?.options||{}).filter(Boolean).join(' / ');};hint();op.onchange=hint;field.onchange=hint;
   del.onclick=()=>{row.remove();status.textContent='Změny podmínek potvrďte tlačítkem Použít podmínky.';};row.append(field,op,value,del);rows.append(row);
  }
  function render(origin){
   if(origin!==box){query.value=c.state.query;for(const [k,e] of Object.entries(quick))if(e.type==='checkbox')e.checked=c.state[k];else e.value=c.state[k];mode.value=c.state.mode;rows.replaceChildren();c.state.rules.forEach(addRow);details.open=!!c.state.rules.length;}
   status.textContent=(c.state.rules.length?'Použito podmínek: '+c.state.rules.length+'. ':'')+(!c.persistent?'Prohlížeč nepovolil uložení filtru; po přechodu na jinou stránku se nezachová.':'');
   if(c.state.rules.some(r=>!Object.hasOwn(c.fields,r.field)))status.textContent+=' Některý sloupec není dostupný. Upravte nebo zrušte jeho podmínku.';
  }
  query.oninput=()=>c.set({...c.state,query:query.value},box);
  add.onclick=()=>{if(rows.children.length<100){addRow();details.open=true;}};
  apply.onclick=()=>c.set({...c.state,mode:mode.value,rules:[...rows.children].map(r=>({field:r.children[0].value,op:r.children[1].value,value:r.children[2].value}))},box);
  reset.onclick=()=>c.set(defaults());render();return c.subscribe(render);
 }
 const api={create,matches,ruleMatch,defaults,clean,inspection,urgency,text,isActive,notice};root.SVMFilters=api;
 if(typeof module!=='undefined')module.exports=api;
})(typeof window!=='undefined'?window:globalThis);


