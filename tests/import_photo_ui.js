'use strict';
const {JSDOM}=require('jsdom'),fs=require('node:fs'),assert=require('node:assert/strict');
(async()=>{
 const dom=new JSDOM('<fieldset id="photoInputs"></fieldset><input id="photoFolder"><input id="photoFiles"><input id="photoKind" value="trees"><input id="photoMode" value="folders"><input id="photoCode"><input id="photoCsv"><button id="photoPreview"></button><button id="photoCommit"></button><p id="photoStatus"></p><table><tbody id="photoRows"></tbody></table>',{runScripts:'outside-only'}),w=dom.window;
 w.ProjectTools={csrf:'token',project_id:1};Object.defineProperty(w,'crypto',{value:require('node:crypto').webcrypto});Object.defineProperty(w.document.getElementById('photoCsv'),'files',{value:[]});
 let preview,uploads=0;w.fetch=async(url,opts)=>{if(url.endsWith('preview')){preview=JSON.parse(opts.body);return {ok:true,json:async()=>({token:'plan'})};}uploads++;return {ok:true,json:async()=>({ok:true})};};
 w.eval(fs.readFileSync(require.resolve('../map/public/project_tools/import_photos.js'),'utf8'));
 const file=(path,size=10)=>({name:path.split('/').pop(),webkitRelativePath:path,size,arrayBuffer:async()=>new ArrayBuffer(1)});
 w.document.getElementById('photoFolder').onchange({target:{files:[file('root/Strom_01/a.jpg'),file('root/strom2/b.jpg'),file('root/strom3/c.jpg',6*1024*1024),file('root/unknown/d.jpg')]}});
 await w.document.getElementById('photoPreview').onclick();assert.equal(preview.rows.length,2);assert.equal(preview.rows[0].code,'1');
 const checks=w.document.querySelectorAll('#photoRows input');assert.equal(checks.length,4);assert.equal(checks[2].checked,false);assert.equal(checks[3].checked,false);checks[1].checked=false;
 await w.document.getElementById('photoCommit').onclick();assert.equal(uploads,1);assert.match(w.document.getElementById('photoStatus').textContent,/Uloženo 1, přeskočeno 3/);console.log('PASS photo import skips oversized/unknown folders and user-unchecked files');w.close();
})().catch(e=>{console.error(e);process.exit(1);});
