#!/usr/bin/env python3
"""Build the signed file-only update from 2.25.2. Never publishes."""
import argparse,pathlib,tempfile,shutil,subprocess
p=argparse.ArgumentParser();p.add_argument('--key',required=True);p.add_argument('--output-dir',required=True);a=p.parse_args()
r=pathlib.Path(__file__).resolve().parent.parent;out=pathlib.Path(a.output_dir).resolve();out.mkdir(parents=True,exist_ok=True);key=pathlib.Path(a.key).resolve()
if key.is_relative_to(r):raise SystemExit('Private key must remain outside repository')
if (r/'app/VERSION').read_text().strip()!='2.25.3':raise SystemExit('Unexpected source version')
if subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout']).strip()!=(r/'app/update-publisher.pem').read_bytes().strip():raise SystemExit('Publisher key mismatch')
archive=out/'update-2.25.3.zip'
if archive.exists():raise SystemExit('Output already exists')
files=['app/includes/footer.php', 'app/includes/header.php', 'assets/css/admin_style.css', 'clients.php', 'map/public/community/community.css', 'map/public/community/community.js', 'map/public/community/index.php', 'map/public/project_tools/area_card.php', 'map/public/project_tools/cards.php', 'map/public/project_tools/export.php', 'map/public/project_tools/import.php', 'map/public/project_tools/import_gpkg.php', 'map/public/project_tools/import_photos.php', 'map/public/project_tools/overview.php', 'map/public/project_tools/public_settings.php', 'map/public/project_tools/settings.php', 'map/public/project_tools/table.php', 'map/public/project_tools/templates.php', 'map/public/project_tools/trees.php', 'project_users.php', 'projects.php', 'users.php']
with tempfile.TemporaryDirectory() as td:
 payload=pathlib.Path(td)
 for name in files:
  f=r/name
  if f.is_symlink() or not f.is_file():raise SystemExit('Invalid source '+name)
  dest=payload/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(f,dest)
 subprocess.run(['python3',str(r/'release-tools/build.py'),'--payload',str(payload),'--from-version','2.25.2','--version','2.25.3','--key',str(key),'--notes','Boční ovládání polohy a Zobrazit vše ve veřejné mapě; návraty na dashboard uvnitř panelů nahoře a dole. Aktualizace z 2.25.2, bez změn databáze.','--output',str(archive)],check=True)
