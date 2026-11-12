'use strict';
const assert=require('node:assert/strict'),P=require('../map/public/project_tools/import_photos.js');
const a={name:'a.jpg',webkitRelativePath:'root/strom66/a.jpg'},b={name:'a.jpg',webkitRelativePath:'root/strom67/a.jpg'},c={name:'b.jpg',webkitRelativePath:'root/68/b.jpg'};
assert.deepEqual(P.assignments([a,b,c],'folders','trees','', '').map(x=>x.code),['66','67','68']);
assert.deepEqual(P.assignments([a,b],'single','trees','55','').map(x=>x.code),['55','55']);
assert.throws(()=>P.assignments([a,b],'csv','trees','','soubor;cislo\na.jpg;66'),/jednozna/);
assert.throws(()=>P.assignments([a],'csv','trees','','soubor;cislo\na.jpg;66\na.jpg;66'),/vícekrát/);
const result=P.assignments([a,b],'csv','trees','','soubor;cislo;popisek\nstrom66/a.jpg;66;"Koruna; detail"\nstrom67/a.jpg;67;Kmen');assert.equal(result[0].note,'Koruna; detail');assert.equal(result[1].code,'67');
assert.throws(()=>P.assignments([{name:'x.jpg'}],'folders','trees','',''),/určit/);
console.log('PASS photo folder/single/CSV assignment, duplicate names/rows rejected, one-photo-per-row notes');

assert.equal(P.assignments([a],'csv','trees','',`soubor;cislo;popisek\na.jpg;66;"'=1+1"`)[0].note,'=1+1');
