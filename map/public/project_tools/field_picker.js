'use strict';
window.FormStructureFields={
 groups:{
 trees:[['Identifikace',['species','scientific_author','czech','angio_gymno','cultivated_variety']],['Dendrometrie',['dbh1_cm','dbh2_cm','dbh3_cm','dbh4_cm','girth1_cm','girth2_cm','girth3_cm','girth4_cm','height','canopy_h_m','spread_m']],['Hodnocení stromu',['phy_age','age','vitality','tree_health','stability','operational_safety','longevity','ecological_value','landscape_value']],['Poznámka ke stromu',['note_tree']],['Plán péče',['treatment_u0','costs_tu0','treatment_u1','costs_tu1','treatment_u2','costs_tu2','treatment_u3','costs_tu3','note_treatment','treatment_history']],['Termíny',['planted','measured_at','last_insp_at','next_insp_at']],['Další poznámka',['note_public']]],
 areas:[['Identifikace',['name']],['Druhové složení a rozměry',['species','height_min','height_max']],['Podklady AOPK',['components','suitability','management','biology','attractiveness']],['Péče a poznámky',['state_note','treatments','urgency','note']],['Termíny',['planted','measured_at','last_insp_at','next_insp_at']]]},
 render(container,catalog,kind,hidden,onChange){
 container.replaceChildren();const grouped=new Set();
 const add=(title,keys)=>{const available=keys.filter(k=>Object.hasOwn(catalog,k));if(!available.length)return;const group=PT.el('fieldset'),legend=PT.el('legend',title),grid=PT.el('div');grid.className='structure-field-grid';group.append(legend,grid);
 for(const k of available){grouped.add(k);const label=PT.el('label'),c=PT.el('input');c.type='checkbox';c.checked=!hidden.includes(k);c.dataset.field=k;c.onchange=()=>onChange(k,c.checked);label.append(c,document.createTextNode(' '+catalog[k].label));grid.append(label);}container.append(group);};
 for(const [title,keys] of this.groups[kind])add(title,kind==='trees'?Object.keys(catalog).filter(k=>keys.includes(k)):keys);
 add('Další pole',Object.keys(catalog).filter(k=>!grouped.has(k)));
 }
};
