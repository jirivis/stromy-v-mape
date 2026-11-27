#!/usr/bin/env python3
"""Build the signed file-only update from 2.25.8. Never publishes."""
import argparse,pathlib,tempfile,shutil,subprocess
p=argparse.ArgumentParser();p.add_argument('--key',required=True);p.add_argument('--output-dir',required=True);a=p.parse_args()
r=pathlib.Path(__file__).resolve().parent.parent;out=pathlib.Path(a.output_dir).resolve();out.mkdir(parents=True,exist_ok=True);key=pathlib.Path(a.key).resolve()
if key.is_relative_to(r):raise SystemExit('Private key must remain outside repository')
if (r/'app/VERSION').read_text().strip()!='2.25.9':raise SystemExit('Unexpected source version')
if subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout']).strip()!=(r/'app/update-publisher.pem').read_bytes().strip():raise SystemExit('Publisher key mismatch')
archive=out/'update-2.25.9.zip'
if archive.exists():raise SystemExit('Output already exists')
files=['app/tree_share_tokens.php', 'map/public/community/api.php', 'map/public/community/community.js', 'map/public/community/index.php', 'map/public/index.php', 'map/public/js/markers.js', 'map/public/js/popup.js', 'map/public/js/project_context_map.js', 'map/public/js/trees.js', 'map/public/project_tools/editor_exit.js', 'map/public/project_tools/form_theme.css', 'map/public/share_tree.php', 'map/public/tree_form/scripts.php', 'map/public/tree_new.php']
with tempfile.TemporaryDirectory() as td:
 payload=pathlib.Path(td)
 for name in files:
  f=r/name
  if f.is_symlink() or not f.is_file():raise SystemExit('Invalid source '+name)
  dest=payload/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(f,dest)
 subprocess.run(['python3',str(r/'release-tools/build.py'),'--payload',str(payload),'--from-version','2.25.8','--version','2.25.9','--key',str(key),'--notes','Rozestupy a zarovnání tlačítek, opětovné zobrazení nových sdílených odkazů, pařezy ve veřejné mapě podle zveřejněných polí a okolní objekty při ruční opravě polohy. Bez SQL změn.','--output',str(archive)],check=True)

