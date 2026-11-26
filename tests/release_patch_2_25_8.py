"""Verify signed update, manual schema gate, backups and preserved installation data."""
import pathlib,tempfile,shutil,subprocess,zipfile,json,hashlib,base64,sys
repo=pathlib.Path(__file__).resolve().parent.parent
with tempfile.TemporaryDirectory() as td:
 root=pathlib.Path(td);src=root/'source';shutil.copytree(repo,src,ignore=shutil.ignore_patterns('.git','__pycache__'))
 (src/'app/VERSION').write_text('2.25.8\n');key=root/'test.pem'
 subprocess.run(['openssl','genpkey','-algorithm','RSA','-pkeyopt','rsa_keygen_bits:2048','-out',str(key)],check=True,capture_output=True)
 pub=subprocess.check_output(['openssl','pkey','-in',str(key),'-pubout']);(src/'app/update-publisher.pem').write_bytes(pub)
 out=root/'out';subprocess.run(['python3',str(src/'release-tools/package_2_25_8.py'),'--key',str(key),'--output-dir',str(out)],check=True)
 archive=out/'update-2.25.8.zip'
 with zipfile.ZipFile(archive) as z:
  raw=z.read('manifest.json');m=json.loads(raw)
  assert m['from']=='2.25.7' and m['version']=='2.25.8' and m['migration_mode']=='manual-schema'
  assert m['migrations']==['database/migrations/09_tree_event_dates.sql'] and len(m['schema_checks'])==4
  assert {'app/tree_lifecycle.php','map/public/tree_new.php','map/public/areas/index.php'}<=set(m['files'])
  for p,h in m['files'].items():assert hashlib.sha256(z.read('payload/'+p)).hexdigest()==h and z.read('payload/'+p)==(src/p).read_bytes()
  for p in z.namelist():assert b'PRIVATE KEY-----' not in z.read(p)
  (root/'manifest').write_bytes(raw);(root/'signature').write_bytes(base64.b64decode(z.read('signature.txt')))
 subprocess.run(['openssl','dgst','-sha256','-verify',str(src/'app/update-publisher.pem'),'-signature',str(root/'signature'),str(root/'manifest')],check=True)
 if len(sys.argv)>1:
  target=root/'installed';shutil.copytree(src,target);(target/'app/VERSION').write_text('2.25.7\n')
  (target/'private/config.php').write_text('configuration sentinel');(target/'map/public/uploads/preserved.jpg').write_bytes(b'photo sentinel')
  (target/'map/public/tree_new.php').write_text('old form');(target/'app/tree_lifecycle.php').unlink()
  harness=root/'apply.php';harness.write_text('''<?php
require $argv[1].'/app/update.php';require $argv[1].'/app/sql.php';
$db=new mysqli('127.0.0.1','root','','',(int)$argv[4]);$db->query('CREATE DATABASE release_2258');$db->select_db('release_2258');
$db->query('CREATE TABLE trees(id INT PRIMARY KEY, project_id INT, removed_at DATE)');$db->query("INSERT INTO trees VALUES(1,2,'2020-01-01')");
$key=file_get_contents($argv[2]);$m=upInspect($argv[3],$key,'2.25.7');
$blocked=false;try{upApply($argv[3],$key,$db);}catch(RuntimeException $e){$blocked=str_contains($e->getMessage(),'Databáze');}
if(!$blocked||upVersion()!=='2.25.7'||file_get_contents($argv[1].'/map/public/tree_new.php')!=='old form')throw new Exception('Schema gate');
foreach(siStatements(upMigrationSql($argv[3],$m)) as $sql)$db->query($sql);
$before=$db->query('SELECT * FROM trees')->fetch_assoc();
if(upApply($argv[3],$key,$db)!=='2.25.8')throw new Exception('Version');
if($before!==$db->query('SELECT * FROM trees')->fetch_assoc())throw new Exception('Data changed');
if(file_get_contents($argv[1].'/private/config.php')!=='configuration sentinel'||file_get_contents($argv[1].'/map/public/uploads/preserved.jpg')!=='photo sentinel')throw new Exception('User files changed');
$backups=glob(upDir().'/backup-*');if(count($backups)!==1||file_get_contents($backups[0].'/map/public/tree_new.php')!=='old form')throw new Exception('Backup');
if(is_file(upDir().'/maintenance.json'))throw new Exception('Maintenance');
echo "PASS signed 2.25.7 -> 2.25.8: schema gate, extracted SQL, preserved data/config/photos, backup\\n";
''')
  subprocess.run(['php',str(harness),str(target),str(src/'app/update-publisher.pem'),str(archive),sys.argv[1]],check=True)
  for p in m['files']:assert (target/p).read_bytes()==(src/p).read_bytes()
