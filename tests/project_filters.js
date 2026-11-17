'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs');
const F=require('../map/public/js/project_filters.js');
const fields={usr_id:{label:'Číslo',type:'number'},height:{label:'Výška',type:'number'},species:{label:'Druh'},status:{label:'Stav'},vitality:{label:'Vitalita',options:{1:'Výborná'}},next_insp_at:{label:'Kontrola',type:'date'}};
const rule=(field,op,value='')=>({field,op,value});
assert.equal(F.ruleMatch({height:null},rule('height','eq','0'),fields),false);
assert.equal(F.ruleMatch({height:0},rule('height','eq','0'),fields),true);
assert.equal(F.ruleMatch({height:0},rule('height','empty'),fields),false);
assert.equal(F.ruleMatch({height:null},rule('height','empty'),fields),true);
assert.equal(F.ruleMatch({height:'2.5'},rule('height','gt','2,4'),fields),true);
assert.equal(F.ruleMatch({height:'oops'},rule('height','lt','3'),fields),false);
assert.equal(F.ruleMatch({vitality:1},rule('vitality','eq','vyborna'),fields),true);
assert.equal(F.ruleMatch({vitality:1},rule('vitality','eq','1'),fields),true);
assert.equal(F.ruleMatch({species:'Bříza'},rule('species','contains','briza'),fields),true);
assert.equal(F.ruleMatch({secret:12},rule('secret','ne','13'),fields),false);
const now=new Date(2026,0,1);assert.equal(F.inspection({next_insp_at:'2026-04-01'},now),'soon');assert.equal(F.inspection({next_insp_at:'2026-04-02'},now),'future');assert.equal(F.inspection({next_insp_at:'2025-12-31'},now),'overdue');assert.equal(F.inspection({},now),'missing');
assert.equal(F.urgency({treatment_u0:' ',treatment_u1:'řez',treatment_u3:'řez'}),'u1');
assert.equal(F.matches({status:'removed'},{...F.defaults(),showRemoved:false},fields,'trees'),false);
assert.equal(F.matches({status:'existing'},{...F.defaults(),status:'removed'},fields,'areas'),false);
assert.equal(F.matches({height:2,species:'Buk'},{...F.defaults(),mode:'any',rules:[rule('height','gt','5'),rule('species','eq','buk')]},fields,'trees'),true);
assert.equal(F.matches({height:2,species:'Buk'},{...F.defaults(),mode:'all',rules:[rule('height','gt','5'),rule('species','eq','buk')]},fields,'trees'),false);
console.log('PASS shared filter comparisons, NULL/zero, dates, codes/labels, all/any and quick filters');

// Actual table script + two copies of the actual panel: no duplicated evaluator mocks.
const {JSDOM}=require('jsdom');
const html=fs.readFileSync('map/public/project_tools/table.php','utf8').replace(/<\?php[\s\S]*?\?>/g,'').replace(/<script[\s\S]*?<\/script>/g,'');
const dom=new JSDOM(html,{url:'https://example.test/map/public/project_tools/table.php',runScripts:'outside-only'}),w=dom.window;
w.ProjectTools={user_id:7,project_id:8,can_edit:true};
const rows=[{id:1,values:{usr_id:1,height:null,species:'Buk'}},{id:2,values:{usr_id:2,height:0,species:'Bříza'}},{id:3,values:{usr_id:3,height:15,species:'Dub'}}];
w.PT={el:(tag,text)=>{const n=w.document.createElement(tag);if(text)n.textContent=text;return n;},status:()=>{},api:async action=>({rows:action.endsWith('areas')?[]:rows,fields,table_fields:fields,can_edit:true})};
w.eval(fs.readFileSync('map/public/js/project_filters.js','utf8'));
(async()=>{
 await w.eval(fs.readFileSync('map/public/project_tools/table.js','utf8'));
 const c=w.SVMFilters.create('trees',fields),mapHost=w.document.createElement('div');w.document.body.append(mapHost);c.mount(mapHost);
 const count=()=>w.document.querySelectorAll('#rows tr').length;
 assert.equal(count(),3);
 w.document.querySelector('#rows input[type=checkbox]').click();
 const query=mapHost.querySelector('input[type=search]');query.value='briza';query.dispatchEvent(new w.Event('input'));
 assert.equal(count(),1);assert.equal(w.document.querySelector('#sharedFilters input[type=search]').value,'briza');
 assert.equal(w.SVMFilters.create('trees',fields,{user_id:7,project_id:9}).state.query,'');
 assert.equal(w.SVMFilters.create('trees',fields,{user_id:9,project_id:8}).state.query,'');
 assert.equal(w.SVMFilters.create('areas',fields).state.query,'');
 c.set({...c.state,query:'',rules:[rule('height','empty')]});assert.equal(count(),1);assert.equal(w.document.querySelector('#rows input[type=checkbox]').checked,true);
 // The same raw values match on the map; legacy display coercion must not turn NULL into zero.
 w.TreeApp={initTreeFilterControl(){},map:{getContainer:()=>w.document.body},applyTreeFilters(){},updateTreeFilterCount(){}};w.TreeAppUser={userId:7,projectId:8};w.fetch=async()=>({ok:true,json:async()=>({project_id:8,trees:fields,areas:fields})});
 w.eval(fs.readFileSync('map/public/js/advanced_filters.js','utf8'));await new Promise(r=>setTimeout(r,0));
 assert.equal(w.TreeApp.treeMatchesActiveFilters({height:0,filter_values:rows[0].values}),true);
 assert.equal(w.TreeApp.treeMatchesActiveFilters({height:0,filter_values:rows[1].values}),false);
 const key='svm-filters:v1:7:8:trees',saved=w.localStorage.getItem(key);assert.equal(JSON.parse(saved).rules[0].op,'empty');
 // A remote tab updates both rendered panels and table results, without touching selection.
 w.dispatchEvent(new w.StorageEvent('storage',{key,newValue:JSON.stringify({...F.defaults(),query:'dub'})}));assert.equal(count(),1);assert.equal(query.value,'dub');
 c.set(F.defaults());assert.equal(count(),3);assert.equal(w.document.querySelector('#rows input[type=checkbox]').checked,true);
 // Conditions are draft until applied; reset clears quick and advanced filters together.
 const button=(root,label)=>[...root.querySelectorAll('button')].find(b=>b.textContent===label);
 button(mapHost,'Přidat podmínku').click();const row=mapHost.querySelector('.svm-filter-rule');row.children[0].value='height';row.children[1].value='gt';row.children[2].value='10';assert.equal(count(),3);button(mapHost,'Použít podmínky').click();assert.equal(count(),1);
 button(mapHost,'Zrušit všechny filtry').click();assert.equal(count(),3);
 // Navigation/refresh restores the exact state, not just controls in the current page.
 const next=new JSDOM('<div id="host"></div>',{url:w.location.href,runScripts:'outside-only'});next.window.localStorage.setItem(key,saved);next.window.eval(fs.readFileSync('map/public/js/project_filters.js','utf8'));
 const restored=next.window.SVMFilters.create('trees',fields,{user_id:7,project_id:8});assert.equal(restored.matches(rows[0].values),true);assert.equal(restored.matches(rows[1].values),false);
 next.window.close();w.close();console.log('PASS real table/map integration, panel synchronization, persistence, scope isolation and independent checkbox selection');
})().catch(e=>{console.error(e);w.close();process.exitCode=1;});
