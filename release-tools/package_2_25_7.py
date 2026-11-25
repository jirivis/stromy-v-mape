#!/usr/bin/env python3
"""Build the signed file-only update from 2.25.6. Never publishes."""
import argparse,pathlib,tempfile,shutil,subprocess
p=argparse.ArgumentParser();p.add_argument('--key',required=True);p.add_argument('--output-dir',required=True);a=p.parse_args()
r=pathlib.Path(__file__).resolve().parent.parent;out=pathlib.Path(a.output_dir).resolve();out.mkdir(parents=True,exist_ok=True);key=pathlib.Path(a.key).resolve()
if key.is_relative_to(r):raise SystemExit('Private key must remain outside repository')
if (r/'app/VERSION').read_text().strip()!='2.25.7':raise SystemExit('Unexpected source version')
if subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout']).strip()!=(r/'app/update-publisher.pem').read_bytes().strip():raise SystemExit('Publisher key mismatch')
archive=out/'update-2.25.7.zip'
if archive.exists():raise SystemExit('Output already exists')
files=['app/form_behavior.php', 'map/public/areas/api.php', 'map/public/areas/areas.css', 'map/public/areas/areas.js', 'map/public/areas/boundary_editor.js', 'map/public/areas/index.php', 'map/public/areas/validation.php', 'map/public/community/community.js', 'map/public/community/index.php', 'map/public/index.php', 'map/public/js/areas_layer.js', 'map/public/js/markers.js', 'map/public/js/project_context_map.js', 'map/public/project_tools/api.php', 'map/public/project_tools/area_card.js', 'map/public/project_tools/area_card.php', 'map/public/project_tools/common.php', 'map/public/project_tools/porost_report.js', 'map/public/project_tools/settings.js', 'map/public/project_tools/settings.php', 'map/public/tree_form/scripts.php', 'map/public/tree_save.php']
with tempfile.TemporaryDirectory() as td:
 payload=pathlib.Path(td)
 for name in files:
  f=r/name
  if f.is_symlink() or not f.is_file():raise SystemExit('Invalid source '+name)
  dest=payload/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(f,dest)
 subprocess.run(['python3',str(r/'release-tools/build.py'),'--payload',str(payload),'--from-version','2.25.6','--version','2.25.7','--key',str(key),'--notes','Značky nejvýše 48 px, nastavení chování formulářů po uložení, autoři taxonů a přehlednější karty ploch, okolní objekty a přiblížení na plochu, průhledné plochy nad body, nápovědy hodnocení AOPK a fotografie pod uložením. Přechod z 2.25.6, bez změn databáze.','--output',str(archive)],check=True)

