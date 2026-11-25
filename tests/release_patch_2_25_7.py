"""Exercise the 2.25.6 -> 2.25.7 builder and updater with a disposable signing key."""
import pathlib,tempfile,shutil,subprocess,zipfile,json,hashlib
repo=pathlib.Path(__file__).resolve().parent.parent
with tempfile.TemporaryDirectory() as td:
 root=pathlib.Path(td);src=root/'source';shutil.copytree(repo,src,ignore=shutil.ignore_patterns('.git','__pycache__'))
 (src/'app/VERSION').write_text('2.25.7\n');key=root/'test.pem'
 subprocess.run(['openssl','genpkey','-algorithm','RSA','-pkeyopt','rsa_keygen_bits:2048','-out',str(key)],check=True,capture_output=True)
 pub=subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout']);(src/'app/update-publisher.pem').write_bytes(pub)
 out=root/'out';subprocess.run(['python3',str(src/'release-tools/package_2_25_7.py'),'--key',str(key),'--output-dir',str(out)],check=True)
 archive=out/'update-2.25.7.zip'
 with zipfile.ZipFile(archive) as z:
  m=json.loads(z.read('manifest.json'));assert m['from']=='2.25.6' and m['version']=='2.25.7' and m['migrations']==[]
  assert len(m['files'])==22 and {'app/form_behavior.php','map/public/js/project_context_map.js','map/public/areas/validation.php'} <= set(m['files'])
  for p,h in m['files'].items():assert hashlib.sha256(z.read('payload/'+p)).hexdigest()==h and z.read('payload/'+p)==(src/p).read_bytes()
  for p in z.namelist():assert b'PRIVATE KEY-----' not in z.read(p)
 target=root/'installed';shutil.copytree(src,target)
 (target/'app/VERSION').write_text('2.25.6\n');(target/'private/config.php').write_text('configuration sentinel')
 (target/'map/public/uploads/preserved.jpg').write_bytes(b'photo sentinel')
 (target/'map/public/areas/areas.js').write_text('old tree save');(target/'map/public/community/community.js').unlink()
 harness=root/'apply.php';harness.write_text('''<?php
require $argv[1].'/app/update.php';
$public=file_get_contents($argv[2]);$db=new mysqli();
if(upApply($argv[3],$public,$db)!=='2.25.7')throw new Exception('Version');
if(file_get_contents($argv[1].'/private/config.php')!=='configuration sentinel')throw new Exception('Config changed');
if(file_get_contents($argv[1].'/map/public/uploads/preserved.jpg')!=='photo sentinel')throw new Exception('Photo changed');
if(!is_file($argv[1].'/map/public/community/community.js'))throw new Exception('Missing dependency');
$backups=glob(upDir().'/backup-*');if(count($backups)!==1||file_get_contents($backups[0].'/map/public/areas/areas.js')!=='old tree save')throw new Exception('Backup');
if(is_file(upDir().'/maintenance.json'))throw new Exception('Maintenance');
echo "PASS signed 2.25.6 -> 2.25.7: no database access, dependency installed, config/photos preserved, backup created\\n";
''')
 subprocess.run(['php',str(harness),str(target),str(src/'app/update-publisher.pem'),str(archive)],check=True)
 for p in m['files']:assert (target/p).read_bytes()==(src/p).read_bytes()

