#!/usr/bin/env python3
"""Build matching fresh-install and 2.23.0 update artifacts; never publishes."""
import argparse, pathlib, tempfile, shutil, subprocess, zipfile
p=argparse.ArgumentParser();p.add_argument('--key',required=True);p.add_argument('--output-dir',required=True);a=p.parse_args()
r=pathlib.Path(__file__).resolve().parent.parent;out=pathlib.Path(a.output_dir).resolve();out.mkdir(parents=True,exist_ok=True)
key=pathlib.Path(a.key).resolve()
if key.is_relative_to(r):raise SystemExit('Private key must be outside repository')
if (r/'app/VERSION').read_text().strip()!='2.24.0':raise SystemExit('Unexpected source version')
for n in ['install-2.24.0.zip','update-2.24.0.zip','bootstrap-2.23.0.zip','release.json']:
 if (out/n).exists():raise SystemExit('Output exists: '+n)
# Check publisher key before signing anything.
pub=subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout'])
if pub.strip()!=(r/'app/update-publisher.pem').read_bytes().strip():raise SystemExit('Publisher key mismatch')
with zipfile.ZipFile(out/'install-2.24.0.zip','w',zipfile.ZIP_DEFLATED) as z:
 for f in sorted(r.rglob('*')):
  n=f.relative_to(r).as_posix()
  if f.is_symlink():raise SystemExit('Symlink: '+n)
  if not f.is_file() or n.split('/')[0] in ['.git','tests','release-tools'] or '__pycache__' in n:continue
  if n.startswith('private/') and n not in ['private/.htaccess','private/config.example.php','private/storage/area_photos/.gitkeep']:raise SystemExit('Unexpected private file: '+n)
  if n.startswith(('map/public/photos/','map/public/uploads/')) and not n.endswith(('/.gitkeep','/.htaccess')):raise SystemExit('Unexpected photo: '+n)
  z.write(f,n)
files=['app/photo_preview.php', 'map/public/project_tools/photo_export_helpers.php', 'map/public/project_tools/photo_caption.php', 'map/public/project_tools/photo_gallery.php', 'map/public/project_tools/qgis_export.php', 'assets/fonts/DejaVuSans-Export.ttf', 'assets/fonts/LICENSE-DejaVu.txt', 'map/public/project_tools/export.php','user_new.php', 'user_edit.php', 'project_new.php', 'project_edit.php', 'project_users.php', 'client_new.php', 'client_edit.php', 'switch_project.php', 'projects.php', 'accept_invitation.php','app/auth_limit.php','auth.php','auth_api.php','app/auth_form.php','app/account.php','login.php','login_process.php','forgot_password.php','reset_password.php','map/public/photo.php','map/public/tree_remove.php','map/public/tree_restore.php','map/public/share_tree.php','map/public/public_tree.php','map/public/tree_save.php','map/public/uploads/.htaccess','map/public/photos/.htaccess','map/public/tree_delete.php','map/public/tree_new.php','map/public/tree_history_save.php','map/public/api/tree_measurements.php','map/public/api/save_tree_measurement.php','map/public/js/editor.js','app/update.php','app/update_database.php','app/update-publisher.pem','updates/index.php','docs/AKTUALIZACE.md','docs/OCENENI.md','database/migrations/07_valuation_prepare.sql','database/migrations/07_valuation_checks.json']
with tempfile.TemporaryDirectory() as td:
 payload=pathlib.Path(td)
 for n in files:
  dest=payload/n;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copyfile(r/n,dest)
 subprocess.run(['python3',str(r/'release-tools/build.py'),'--payload',str(payload),'--from-version','2.23.0','--version','2.24.0','--key',str(key),'--notes','Aktualizátor pro WEDOS a příprava ocenění stromů a ploch. Vyžaduje jednorázový bootstrap původní 2.23.0 a SQL přípravu.','--migration','database/migrations/07_valuation_prepare.sql','--schema-checks',str(r/'database/migrations/07_valuation_checks.json'),'--output',str(out/'update-2.24.0.zip'),'--download-url','https://github.com/jirivis/stromy-v-mape/releases/download/v2.24.0/update-2.24.0.zip'],check=True)
with zipfile.ZipFile(out/'bootstrap-2.23.0.zip','w',zipfile.ZIP_DEFLATED) as z:
 for n in ['app/update.php','app/update_database.php','app/update-publisher.pem','updates/index.php']:z.write(r/n,n)
shutil.copyfile(r/'database/migrations/07_valuation_prepare.sql',out/'priprava-oceneni.sql')
shutil.copyfile(r/'app/update-publisher.pem',out/'publisher-public.pem')
print('Prepared locally; no release published:',out)
