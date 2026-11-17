"""Fresh HTTP installation and full synthetic backup/restore; isolated CI database only."""
import pathlib, tempfile, shutil, subprocess, sys, time, http.client, urllib.parse, re, hashlib, zipfile, json, os, pwd, grp, urllib.request, urllib.error
repo=pathlib.Path(__file__).resolve().parent.parent
port=sys.argv[1]; dbname='installation_restore_test'
base=['--no-defaults','--protocol=tcp','-h127.0.0.1','-P'+port,'-uroot']
def sql(q): return subprocess.check_output(['mariadb',*base,'-N','-e',q]).decode().strip()
def dump(): return subprocess.check_output(['mariadb-dump',*base,'--single-transaction','--hex-blob','--skip-comments','--skip-dump-date',dbname])
sql('CREATE DATABASE '+dbname)
with tempfile.TemporaryDirectory() as td:
 root=pathlib.Path(td); archive=root/'installer.zip'
 subprocess.run(['python3',str(repo/'release-tools/package_installer.py'),'--output',str(archive)],check=True)
 second=root/'second.zip'
 subprocess.run(['python3',str(repo/'release-tools/package_installer.py'),'--output',str(second)],check=True)
 assert archive.read_bytes()==second.read_bytes(), 'Installer must be reproducible'
 with zipfile.ZipFile(archive) as z:
  assert not any(n.startswith(('application/tests/','application/release-tools/','application/.git/')) for n in z.namelist())
  assert 'application/private/config.php' not in z.namelist()
  for line in z.read('SHA256SUMS.txt').decode().splitlines():
   digest,name=line.split('  ',1);assert hashlib.sha256(z.read(name)).hexdigest()==digest,name
  z.extractall(root/'unpacked')
 app=root/'unpacked/application';sessions=root/'sessions';sessions.mkdir()
 assert (app/'private/storage/area_photos').is_dir()
 assert (app/'map/public/photos/.htaccess').is_file() and (app/'map/public/uploads/.htaccess').is_file()
 cfg=subprocess.check_output(['php','-r', '$c=require $argv[1]; echo json_encode($c);',str(app/'private/config.example.php')])
 cfg=json.loads(cfg);assert cfg['updates']['repository']=='jirivis/stromy-v-mape-updates' and cfg['updates']['check_on_dashboard'] is True
 assert cfg['updates']['public_key']==(app/'app/update-publisher.pem').read_text()
 assert cfg['database']['password']=='' and cfg['installation_key']==''

 config="<?php return ['installation_key'=>'"+'k'*48+"','app'=>['url'=>'http://127.0.0.1:33088'],'database'=>['host'=>'127.0.0.1','port'=>"+port+",'user'=>'root','password'=>'','name'=>'"+dbname+"']];"
 (app/'private/config.php').write_text(config)
 (app/'private/installed.lock').unlink(missing_ok=True)
 cookie=''
 def request(path,data=None):
  global cookie
  c=http.client.HTTPConnection('127.0.0.1',33088);headers={'Cookie':cookie}
  if data is not None:headers['Content-Type']='application/x-www-form-urlencoded'
  c.request('POST' if data is not None else 'GET',path,urllib.parse.urlencode(data) if data is not None else None,headers)
  r=c.getresponse();body=r.read().decode();status=r.status;h=dict(r.getheaders());c.close()
  if 'Set-Cookie' in h:cookie=h['Set-Cookie'].split(';')[0]
  return status,body
 def token(body,name):return re.search(r'name="'+name+r'" value="([a-f0-9]+)"',body)[1]
 with (root/'server.log').open('w') as log:
  server=subprocess.Popen(['php','-d','session.save_path='+str(sessions),'-S','127.0.0.1:33088','-t',str(app)],stdout=log,stderr=log)
  try:
   for _ in range(80):
    try:status,body=request('/install/');break
    except ConnectionRefusedError:time.sleep(.1)
   assert status==200 and 'CHYBÍ' not in body,body
   csrf=token(body,'csrf');data=dict(csrf=csrf,installation_key='k'*48,confirm_empty='yes',username='restore-admin',email='restore@example.invalid',password='Restore-test-pass-123',password_again='Restore-test-pass-123')
   request('/install/',dict(data,installation_key='wrong'))
   assert sql('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema="'+dbname+'"')=='0'
   status,body=request('/install/',data);assert status==200 and 'Instalace dokončena' in body,body
   assert (app/'private/installed.lock').exists()
   assert sql('SELECT COUNT(*) FROM '+dbname+'.users WHERE is_super_admin=1')=='1'
   assert sql('SELECT COUNT(*) FROM '+dbname+'.species')=='557'
   assert sql('SELECT COUNT(*) FROM '+dbname+'.cultivars')=='1099'
   assert sql('SELECT COUNT(*) FROM '+dbname+'.svm_public_maps WHERE enabled=1')=='0'
   request('/install/',data);assert sql('SELECT COUNT(*) FROM '+dbname+'.users')=='1'
   # Even loss of the lock file must not allow installation over existing data.
   (app/'private/installed.lock').unlink()
   _,body=request('/install/');data['csrf']=token(body,'csrf')
   _,body=request('/install/',data);assert 'není prázdná' in body,body
   (app/'private/installed.lock').write_text('test installation locked\n')
   sql("USE "+dbname+"; INSERT INTO projects(id,name,owner_id) VALUES(701,'Obnova žluťoučká',1); INSERT INTO trees(id,project_id,usr_id,species,age) VALUES(702,701,66,'Quercus robur',1000); INSERT INTO trees_photo(tree_id,url,status,note_photo) VALUES(702,'uploads/restore.png','active','Popisek fotografie');")
   photo=bytes.fromhex('89504e470d0a1a0a')+b'synthetic-backup-fixture'
   (app/'map/public/uploads/restore.png').write_bytes(photo)
   (app/'private/storage/area_photos/restore.bin').write_bytes(b'synthetic-area-photo')
   snapshot=root/'snapshot';shutil.copytree(app,snapshot);backup=dump()
   # Simulate damaged working data, then restore SQL and the matching application files.
   sql('UPDATE '+dbname+'.trees SET age=1 WHERE id=702')
   (app/'map/public/uploads/restore.png').write_bytes(b'damaged')
   sql('DROP DATABASE '+dbname+'; CREATE DATABASE '+dbname)
   subprocess.run(['mariadb',*base,dbname],input=backup,check=True)
   shutil.copytree(snapshot,app,dirs_exist_ok=True)
   assert dump()==backup,'Restored database differs from backup'
   assert (app/'private/config.php').read_text()==config
   assert (app/'map/public/uploads/restore.png').read_bytes()==photo
   assert (app/'private/storage/area_photos/restore.bin').read_bytes()==b'synthetic-area-photo'
   assert (app/'app/VERSION').read_bytes()==(snapshot/'app/VERSION').read_bytes()
   cookie='';_,body=request('/login.php');csrf=token(body,'auth_form_token')
   status,body=request('/login_process.php',dict(auth_form_token=csrf,username='restore-admin',password='Restore-test-pass-123'));assert status==302,(status,body)
   assert request('/dashboard.php')[0]==200
   if os.environ.get('SVM_APACHE_TEST')=='1':
    root.chmod(0o755)
    conf=root/'apache.conf'
    conf.write_text('\n'.join([
     'ServerRoot "'+str(root)+'"','ServerName localhost','Listen 127.0.0.1:33089',
     'PidFile "'+str(root/'apache.pid')+'"','ErrorLog "'+str(root/'apache.log')+'"',
     'User '+pwd.getpwuid(os.getuid()).pw_name,'Group '+grp.getgrgid(os.getgid()).gr_name,
     *['LoadModule '+m+'_module /usr/lib/apache2/modules/mod_'+m+'.so' for m in ['mpm_event','authz_core','authz_host','rewrite','mime']],
     'TypesConfig /etc/mime.types','DocumentRoot "'+str(app)+'"',
     '<Directory "'+str(app)+'">','Require all granted','AllowOverride All','Options FollowSymLinks','</Directory>'
    ]))
    apache=subprocess.Popen(['apache2','-f',str(conf),'-DFOREGROUND'],stdout=subprocess.DEVNULL,stderr=subprocess.PIPE)
    try:
     def get_private(path):
      try:
       with urllib.request.urlopen('http://127.0.0.1:33089'+path,timeout=5) as resp:return resp.status
      except urllib.error.HTTPError as err:return err.code
     for _ in range(100):
      if apache.poll() is not None:raise AssertionError(apache.stderr.read().decode())
      try:get_private('/private/config.php');break
      except urllib.error.URLError:time.sleep(.1)
     for path in ['/private/config.php','/private/storage/area_photos/restore.bin','/database/schema.sql','/database/catalogs/baseline.sql']:
      assert get_private(path)==403,path
     print('PASS installer ZIP Apache: private configuration, private photos and database SQL denied')
    finally:apache.terminate();apache.wait(timeout=10)
   print('PASS installer ZIP fresh HTTP install: key/lock/nonempty protection, admin/catalogs/private defaults; SQL + files restore exact; restored admin login works')
  except Exception:
   print((root/'server.log').read_text()[-2500:]);raise
  finally:server.terminate();server.wait(timeout=10)
