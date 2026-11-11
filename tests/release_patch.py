"""Build real distribution archives with an ephemeral test key, never the publisher key."""
import pathlib,tempfile,shutil,subprocess,json,zipfile,hashlib,base64,sys
repo=pathlib.Path(__file__).resolve().parent.parent
with tempfile.TemporaryDirectory(prefix='svm-release-test-') as td:
 root=pathlib.Path(td);source=root/'source'
 shutil.copytree(repo,source,ignore=shutil.ignore_patterns('.git','__pycache__'))
 # Exercise the legacy 2.23 -> 2.24 builder independently of current source version.
 (source/'app/VERSION').write_text('2.24.1\n')
 key=root/'test-private.pem'
 subprocess.run(['openssl','genpkey','-algorithm','RSA','-pkeyopt','rsa_keygen_bits:2048','-out',str(key)],check=True,capture_output=True)
 public=subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout'])
 (source/'app/update-publisher.pem').write_bytes(public)
 output=root/'distribution'
 subprocess.run(['python3',str(source/'release-tools/package_2_24_1.py'),'--key',str(key),'--output-dir',str(output)],check=True)
 with zipfile.ZipFile(output/'update-2.24.1.zip') as update,zipfile.ZipFile(output/'install-2.24.1.zip') as install:
  raw=update.read('manifest.json');manifest=json.loads(raw)
  assert manifest['from']=='2.24.0' and manifest['version']=='2.24.1'
  assert manifest['migrations']==[] and 'schema_checks' not in manifest
  required=['map/public/js/project_filters.js', 'map/public/js/advanced_filters.js', 'map/public/advanced_filters.css', 'map/public/project_tools/api.php', 'map/public/project_tools/table.php', 'map/public/project_tools/table.js', 'map/public/api/trees_geojson.php', 'map/public/js/map.js', 'map/public/js/markers.js', 'map/public/js/popup.js', 'map/public/js/table.js', 'map/public/photos/.htaccess', 'map/public/project_tools/gpkg_import.js', 'map/public/tree_form/scripts.php', 'map/public/uploads/.htaccess', 'map/public/project_tools/editor_exit.js']
  assert all(p in manifest['files'] for p in required)
  assert set(update.namelist())=={'manifest.json','signature.txt'}|{'payload/'+p for p in manifest['files']}
  for path,digest in manifest['files'].items():
   content=update.read('payload/'+path)
   assert hashlib.sha256(content).hexdigest()==digest,path
   assert content==(source/path).read_bytes()==install.read(path),path
   assert not path.startswith('private/'),path
  for archive in [update,install]:
   for name in archive.namelist():
    assert not name.endswith('private/config.php'),name
    assert b'PRIVATE KEY-----' not in archive.read(name),name
  assert install.read('app/VERSION').strip()==b'2.24.1'
  for path in ['database/catalogs/baseline.sql','database/catalogs/additions.sql','database/catalogs/powo-ids.sql']:
   assert install.read(path)==(source/path).read_bytes()
  (root/'manifest').write_bytes(raw);(root/'signature').write_bytes(base64.b64decode(update.read('signature.txt'),validate=True));(root/'public.pem').write_bytes(public)
  subprocess.run(['openssl','dgst','-sha256','-verify',str(root/'public.pem'),'-signature',str(root/'signature'),str(root/'manifest')],check=True)
 release=json.loads((output/'release.json').read_text())
 assert release['sha256']==hashlib.sha256((output/'update-2.24.1.zip').read_bytes()).hexdigest()
 print('PASS release archives: valid signature/hashes, bootstrap parity, security payload, fresh catalogs; no private key or live configuration')

 if len(sys.argv)>1:
  target=root/'installed';shutil.copytree(source,target)
  (target/'app/VERSION').write_text('2.24.0\n')
  (target/'private/config.php').write_text('local configuration sentinel')
  (target/'map/public/uploads/preserved.jpg').write_bytes(b'photo sentinel')
  (target/'map/public/js/table.js').write_text('previous table sentinel')
  harness=root/'apply.php'
  harness.write_text("""<?php
require $argv[1].'/app/update.php';
$db=new mysqli('127.0.0.1','root','','valuation_fresh',(int)$argv[4]);
function snapshot(mysqli $db):string{
 $data=[];
 foreach(['trees','svm_areas','trees_photo','svm_area_photos','tree_history','svm_area_history','species','cultivars'] as $table){
  $data[$table]=$db->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetch_all(MYSQLI_ASSOC);
 }
 return hash('sha256',json_encode($data,JSON_THROW_ON_ERROR));
}
$before=snapshot($db);
if(upApply($argv[2],file_get_contents($argv[3]),$db)!=='2.24.1')throw new Exception('Version');
if(snapshot($db)!==$before)throw new Exception('Application data changed');
if(file_get_contents($argv[1].'/private/config.php')!=='local configuration sentinel')throw new Exception('Config changed');
if(file_get_contents($argv[1].'/map/public/uploads/preserved.jpg')!=='photo sentinel')throw new Exception('Photo changed');
if(file_exists(upDir().'/maintenance.json'))throw new Exception('Maintenance retained');
$backups=glob(upDir().'/backup-*');
if(count($backups)!==1||file_get_contents($backups[0].'/map/public/js/table.js')!=='previous table sentinel')throw new Exception('Backup');
echo "PASS actual release update: version advanced, data/config/photo preserved, previous file backed up, maintenance cleared\n";
""")
  subprocess.run(['php',str(harness),str(target),str(output/'update-2.24.1.zip'),str(root/'public.pem'),sys.argv[1]],check=True)
  for path in manifest['files']:
   assert (target/path).read_bytes()==(source/path).read_bytes(),path
