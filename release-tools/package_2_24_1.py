#!/usr/bin/env python3
"""Build matching fresh-install and 2.24.0 update artifacts; never publishes."""
import argparse, pathlib, tempfile, shutil, subprocess, zipfile
p=argparse.ArgumentParser();p.add_argument('--key',required=True);p.add_argument('--output-dir',required=True);a=p.parse_args()
r=pathlib.Path(__file__).resolve().parent.parent;out=pathlib.Path(a.output_dir).resolve();out.mkdir(parents=True,exist_ok=True)
key=pathlib.Path(a.key).resolve()
if key.is_relative_to(r):raise SystemExit('Private key must be outside repository')
if (r/'app/VERSION').read_text().strip()!='2.24.1':raise SystemExit('Unexpected source version')
for n in ['install-2.24.1.zip','update-2.24.1.zip','release.json']:
 if (out/n).exists():raise SystemExit('Output exists: '+n)
# Check publisher key before signing anything.
pub=subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout'])
if pub.strip()!=(r/'app/update-publisher.pem').read_bytes().strip():raise SystemExit('Publisher key mismatch')
with zipfile.ZipFile(out/'install-2.24.1.zip','w',zipfile.ZIP_DEFLATED) as z:
 for f in sorted(r.rglob('*')):
  n=f.relative_to(r).as_posix()
  if f.is_symlink():raise SystemExit('Symlink: '+n)
  if not f.is_file() or n.split('/')[0] in ['.git','tests','release-tools'] or '__pycache__' in n:continue
  if n.startswith('private/') and n not in ['private/.htaccess','private/config.example.php','private/storage/area_photos/.gitkeep']:raise SystemExit('Unexpected private file: '+n)
  if n.startswith(('map/public/photos/','map/public/uploads/')) and not n.endswith(('/.gitkeep','/.htaccess')):raise SystemExit('Unexpected photo: '+n)
  z.write(f,n)
files=['map/public/project_tools/photo_gallery.php', 'map/public/project_tools/export.php', 'map/public/share_tree.php', 'map/public/js/areas_layer.js', 'map/public/areas/areas.js', 'map/public/areas/index.php', 'map/public/js/map.js', 'map/public/js/markers.js', 'map/public/js/popup.js', 'map/public/js/table.js', 'map/public/photos/.htaccess', 'map/public/project_tools/gpkg_import.js', 'map/public/tree_form/scripts.php', 'map/public/uploads/.htaccess', 'map/public/project_tools/editor_exit.js', 'map/public/index.php', 'map/public/tree_new.php', 'docs/OPRAVY_2_24_1.md']
with tempfile.TemporaryDirectory() as td:
 payload=pathlib.Path(td)
 for n in files:
  dest=payload/n;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(r/n,dest)
 subprocess.run(['python3',str(r/'release-tools/build.py'),'--payload',str(payload),'--from-version','2.24.0','--version','2.24.1','--key',str(key),'--notes','Opravy fotografií, čitelnosti mapy, našeptávání a editačního formuláře. Bez změn databáze.','--output',str(out/'update-2.24.1.zip'),'--download-url','https://github.com/jirivis/stromy-v-mape/releases/download/v2.24.1/update-2.24.1.zip'],check=True)
