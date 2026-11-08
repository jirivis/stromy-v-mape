#!/usr/bin/env python3
"""Prepare the first updater release. Never creates or publishes a production key."""
import argparse, pathlib, shutil, subprocess, tempfile, zipfile
p=argparse.ArgumentParser()
p.add_argument('--key', required=True, help='Publisher private PEM outside repository')
p.add_argument('--output-dir', required=True)
a=p.parse_args()
root=pathlib.Path(__file__).resolve().parent.parent
key=pathlib.Path(a.key).resolve()
if key.is_relative_to(root):raise SystemExit('Keep the private key outside the repository')
out=pathlib.Path(a.output_dir).resolve();out.mkdir(parents=True,exist_ok=True)
outputs=[out/'update-2.23.1.zip',out/'bootstrap-updater.zip',out/'release.json']
if any(x.exists() for x in outputs):raise SystemExit('Output already exists; choose an empty output directory')
if (root/'app/VERSION').read_text().strip()!='2.23.0':raise SystemExit('Expected source base 2.23.0')
files=['app/update.php','app/update_database.php','updates/index.php','docs/AKTUALIZACE.md']
with tempfile.TemporaryDirectory() as td:
    payload=pathlib.Path(td)
    for name in files:
        dest=payload/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copyfile(root/name,dest)
    subprocess.run(['python3',str(root/'release-tools/build.py'),'--payload',str(payload),'--from-version','2.23.0','--version','2.23.1','--key',str(key),'--notes','Aktualizátor pro omezený databázový účet a ruční příprava struktury. Bez změn databáze.','--output',str(outputs[0]),'--download-url','https://github.com/jirivis/stromy-v-mape/releases/download/v2.23.1/update-2.23.1.zip'],check=True)
with zipfile.ZipFile(outputs[1],'w',zipfile.ZIP_DEFLATED) as z:
    for name in ['app/update.php','app/update_database.php']:z.write(root/name,name)
    z.writestr('CTI_ME.txt','Jednorázová oprava původní 2.23.0 na WEDOSu. Po záloze nahrajte oba soubory do app/ vedle dashboard.php (složka app je vedle dashboardu). Verzi ani databázi tento ZIP nemění. Nastavte veřejný klíč vydavatele v private/config.php; soukromý klíč na hosting nepatří. Poté v Aktualizacích nahrajte podepsaný update-2.23.1.zip a schvalte instalaci. Tento bootstrap ZIP není podepsaný aktualizační balíček; distribuujte jej důvěryhodnou cestou.')
print('Prepared locally; not published:', out)
