#!/usr/bin/env python3
"""Build the signed file-only update from 2.25.0. Never publishes."""
import argparse,pathlib,tempfile,shutil,subprocess
p=argparse.ArgumentParser();p.add_argument('--key',required=True);p.add_argument('--output-dir',required=True);a=p.parse_args()
r=pathlib.Path(__file__).resolve().parent.parent;out=pathlib.Path(a.output_dir).resolve();out.mkdir(parents=True,exist_ok=True);key=pathlib.Path(a.key).resolve()
if key.is_relative_to(r):raise SystemExit('Private key must remain outside repository')
if (r/'app/VERSION').read_text().strip()!='2.25.1':raise SystemExit('Unexpected source version')
if subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout']).strip()!=(r/'app/update-publisher.pem').read_bytes().strip():raise SystemExit('Publisher key mismatch')
archive=out/'update-2.25.1.zip'
if archive.exists():raise SystemExit('Output already exists')
files=['map/public/community/index.php','map/public/community/api.php','map/public/community/community.js','map/public/community/community.css','map/public/js/markers.js','map/public/project_tools/tools.css','map/public/project_tools/common.php','map/public/project_tools/public_settings.php','map/public/project_tools/public_settings.js','docs/VEREJNA_MAPA.md']
with tempfile.TemporaryDirectory() as td:
 payload=pathlib.Path(td)
 for name in files:
  f=r/name
  if f.is_symlink() or not f.is_file():raise SystemExit('Invalid source '+name)
  dest=payload/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(f,dest)
 subprocess.run(['python3',str(r/'release-tools/build.py'),'--payload',str(payload),'--from-version','2.25.0','--version','2.25.1','--key',str(key),'--notes','Veřejná mapa: podklady, značky shodné s hlavní mapou, barvení, společný filtr a tabulka. Hromadný výběr veřejných údajů a užší sloupec Veřejné. Bez SQL změn.','--output',str(archive)],check=True)
