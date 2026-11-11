'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
for(const file of ['map/public/js/popup.js','map/public/tree_form/scripts.php']) {
 const source=fs.readFileSync(file,'utf8'),start=source.indexOf('function protectedTreePhotoUrl('),end=source.indexOf('\n}',start)+2;
 const ctx={URL,location:{href:'https://example.test/map/public/tree_new.php?id=1',origin:'https://example.test'}};
 vm.createContext(ctx);vm.runInContext(source.slice(start,end),ctx);
 const result=new URL(ctx.protectedTreePhotoUrl('uploads/a%20b.jpg?token=share'));
 assert.equal(result.pathname,'/map/public/photo.php');assert.equal(result.searchParams.get('file'),'a b.jpg');assert.equal(result.searchParams.get('token'),'share');
 assert.equal(ctx.protectedTreePhotoUrl('https://other.test/uploads/a.jpg'),'https://other.test/uploads/a.jpg');
 assert.equal(ctx.protectedTreePhotoUrl('photo.php?photo=12&token=abc'),'photo.php?photo=12&token=abc');
}
const source=fs.readFileSync('map/public/js/markers.js','utf8');const a=source.indexOf('TreeApp.getMarkerDimension'),b=source.indexOf('// Styl popisku',a);
const ctx={TreeApp:{}};vm.createContext(ctx);vm.runInContext(source.slice(a,b),ctx);
for(const mode of ['none','girth','dbh','spread','height'])assert.equal(ctx.TreeApp.getMarkerDimension({},mode),28);
assert.equal(ctx.TreeApp.getMarkerDimension({spread_m:20},'spread'),80);
console.log('PASS image gateway URLs and readable minimum marker dimensions');

ctx.window={matchMedia:()=>({matches:true})};
for(const mode of ['none','girth','dbh','spread','height'])assert.equal(ctx.TreeApp.getMarkerDimension({},mode),20);
console.log('PASS touch devices retain original minimum dimensions');
