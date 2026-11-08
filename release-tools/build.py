#!/usr/bin/env python3
"""Build a signed UPDATE ZIP from a directory containing changed application files.
Private RSA key must remain on the publisher's computer, outside the repository.
"""
import argparse,hashlib,json,pathlib,subprocess,tempfile,zipfile,base64,re
p=argparse.ArgumentParser();p.add_argument('--payload',required=True);p.add_argument('--from-version',required=True);p.add_argument('--version',required=True);p.add_argument('--key',required=True);p.add_argument('--notes',required=True);p.add_argument('--output',required=True);p.add_argument('--migration',action='append',default=[]);p.add_argument('--schema-checks',help='JSON list of table/column/type/nullable postconditions for additive schema migrations');p.add_argument('--download-url',default='');a=p.parse_args()
root=pathlib.Path(a.payload).resolve();files={}
for f in sorted(root.rglob('*')):
 if f.is_symlink():raise SystemExit('Symlinks are not permitted')
 if not f.is_file():continue
 name=f.relative_to(root).as_posix()
 if not re.fullmatch(r'[A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)*',name) or '..' in name:raise SystemExit('Invalid path: '+name)
 if name in ['app/VERSION','manifest.json','signature.txt','release.json'] or any(name==x or name.startswith(x+'/') for x in ['private','map/public/photos','map/public/uploads','.git','install']):raise SystemExit('Protected path: '+name)
 files[name]=hashlib.sha256(f.read_bytes()).hexdigest()
for m in a.migration:
 if m not in files or not m.startswith('database/migrations/') or not m.endswith('.sql'):raise SystemExit('Invalid migration '+m)
manifest={'product':'stromy-v-mape','from':a.from_version,'version':a.version,'php_min':'8.2','notes':a.notes,'files':files,'migrations':a.migration}
if a.migration:
 if not a.schema_checks:raise SystemExit('Schema migrations require --schema-checks; data migrations are not supported')
 checks=json.loads(pathlib.Path(a.schema_checks).read_text())
 if not isinstance(checks,list) or not checks:raise SystemExit('Schema checks must be a nonempty list')
 for c in checks:
  if not isinstance(c,dict) or not all(isinstance(c.get(k),str) and re.fullmatch(r'[A-Za-z0-9_]+',c[k]) for k in ['table','column']) or not isinstance(c.get('type'),str) or not re.fullmatch(r'[a-z0-9(), ]+',c['type']) or not isinstance(c.get('nullable'),bool):raise SystemExit('Invalid schema check')
 manifest.update(migration_mode='manual-schema',schema_checks=checks)
elif a.schema_checks:raise SystemExit('--schema-checks requires --migration')
raw=json.dumps(manifest,ensure_ascii=False,separators=(',',':')).encode()
with tempfile.TemporaryDirectory() as td:
 m=pathlib.Path(td)/'manifest';m.write_bytes(raw)
 sig=subprocess.check_output(['openssl','dgst','-sha256','-sign',str(pathlib.Path(a.key).resolve()),str(m)])
 with zipfile.ZipFile(a.output,'w',zipfile.ZIP_DEFLATED) as z:
  z.writestr('manifest.json',raw);z.writestr('signature.txt',base64.b64encode(sig))
  for name in files:z.write(root/name,'payload/'+name)
if a.download_url:
 release={'version':a.version,'url':a.download_url,'sha256':hashlib.sha256(pathlib.Path(a.output).read_bytes()).hexdigest(),'notes':a.notes}
 pathlib.Path(a.output).with_name('release.json').write_text(json.dumps(release,ensure_ascii=False,indent=2))
print('Created',a.output)
