#!/usr/bin/env python3
"""Build the signed file-only update from 2.25.1. Never publishes."""
import argparse,pathlib,tempfile,shutil,subprocess
p=argparse.ArgumentParser();p.add_argument('--key',required=True);p.add_argument('--output-dir',required=True);a=p.parse_args()
r=pathlib.Path(__file__).resolve().parent.parent;out=pathlib.Path(a.output_dir).resolve();out.mkdir(parents=True,exist_ok=True);key=pathlib.Path(a.key).resolve()
if key.is_relative_to(r):raise SystemExit('Private key must remain outside repository')
if (r/'app/VERSION').read_text().strip()!='2.25.2':raise SystemExit('Unexpected source version')
if subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout']).strip()!=(r/'app/update-publisher.pem').read_bytes().strip():raise SystemExit('Publisher key mismatch')
archive=out/'update-2.25.2.zip'
if archive.exists():raise SystemExit('Output already exists')
files=['app/update.php', 'updates/index.php', 'updates/check.php', 'app/includes/header.php', 'app/includes/topbar.php', 'map/public/tree_new.php', 'map/public/areas/index.php', 'map/public/project_tools/common.php', 'map/public/community/index.php', 'map/public/community/community.js', 'docs/AKTUALIZACE.md']
with tempfile.TemporaryDirectory() as td:
 payload=pathlib.Path(td)
 for name in files:
  f=r/name
  if f.is_symlink() or not f.is_file():raise SystemExit('Invalid source '+name)
  dest=payload/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(f,dest)
 subprocess.run(['python3',str(r/'release-tools/build.py'),'--payload',str(payload),'--from-version','2.25.1','--version','2.25.2','--key',str(key),'--notes','Nastavení zdroje aktualizací v administraci, výběr kompatibilního vydání, poloha zařízení ve veřejné mapě a návraty na dashboard. Bez SQL změn.','--output',str(archive)],check=True)
