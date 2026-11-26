#!/usr/bin/env python3
"""Build the signed schema-prepared update from 2.25.7. Never publishes."""
import argparse,pathlib,tempfile,shutil,subprocess
p=argparse.ArgumentParser();p.add_argument('--key',required=True);p.add_argument('--output-dir',required=True);a=p.parse_args()
r=pathlib.Path(__file__).resolve().parent.parent;out=pathlib.Path(a.output_dir).resolve();out.mkdir(parents=True,exist_ok=True);key=pathlib.Path(a.key).resolve()
if key.is_relative_to(r):raise SystemExit('Private key must remain outside repository')
if (r/'app/VERSION').read_text().strip()!='2.25.8':raise SystemExit('Unexpected source version')
if subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout']).strip()!=(r/'app/update-publisher.pem').read_bytes().strip():raise SystemExit('Publisher key mismatch')
archive=out/'update-2.25.8.zip'
if archive.exists():raise SystemExit('Output already exists')
files=['app/public_map.php', 'app/tree_lifecycle.php', 'database/migrations/09_tree_event_dates.sql', 'database/migrations/09_tree_event_dates_checks.json', 'docs/TREE_EVENT_DATES.md', 'map/public/api/get_trees.php', 'map/public/api/trees_geojson.php', 'map/public/areas/areas.css', 'map/public/areas/index.php', 'map/public/community/api.php', 'map/public/community/community.css', 'map/public/community/community.js', 'map/public/community/index.php', 'map/public/index.php', 'map/public/js/popup.js', 'map/public/project_tools/api.php', 'map/public/project_tools/tree_fields.json', 'map/public/tree_card_template.php', 'map/public/tree_form/sections/assessment.php', 'map/public/tree_form/sections/timestamps.php', 'map/public/tree_new.php', 'map/public/tree_remove.php', 'map/public/tree_restore.php', 'map/public/tree_save.php']
with tempfile.TemporaryDirectory() as td:
 payload=pathlib.Path(td)
 for name in files:
  f=r/name
  if f.is_symlink() or not f.is_file():raise SystemExit('Invalid source '+name)
  dest=payload/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(f,dest)
 subprocess.run(['python3',str(r/'release-tools/build.py'),'--payload',str(payload),'--from-version','2.25.7','--version','2.25.8','--key',str(key),'--notes','Otevření karet z formulářů, stav odumření či odstranění v kartě a popupu, skutečná data death_date/removal_date oddělená od data označení. Nejprve po záloze spusťte SQL přípravu z aktualizátoru a zkontrolujte databázi. Bez doplňování historických dat.','--migration','database/migrations/09_tree_event_dates.sql','--schema-checks',str(r/'database/migrations/09_tree_event_dates_checks.json'),'--output',str(archive)],check=True)

