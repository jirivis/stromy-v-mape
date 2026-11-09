import base64,re,urllib.parse,hashlib
import pathlib,tempfile,shutil,subprocess,sys,json,time,urllib.request,urllib.error
repo=pathlib.Path(__file__).resolve().parent.parent;port=sys.argv[1]
subprocess.run(['mariadb','--no-defaults','--protocol=tcp','-h127.0.0.1','-P'+port,'-uroot','valuation_fresh','-e',"INSERT INTO users(id,username,password,active) VALUES(1,'editor','unused',1),(2,'viewer','unused',1); INSERT INTO projects(id,name,owner_id) VALUES(1,'A',1),(2,'B',NULL); INSERT INTO project_users VALUES(1,2,'viewer',NOW()); INSERT INTO trees(id,project_id) VALUES(1,1),(2,2); INSERT INTO tree_measurements(tree_id,measurement_type,value_cm) VALUES(2,'dbh',44);"],check=True)
with tempfile.TemporaryDirectory() as td:
 root=pathlib.Path(td);app=root/'app';shutil.copytree(repo,app);sessions=root/'sessions';sessions.mkdir()
 (app/'private/config.php').write_text("<?php return ['database'=>['host'=>'127.0.0.1','port'=>"+port+",'user'=>'root','password'=>'','name'=>'valuation_fresh']];")
 for sid,uid in [('editor',1),('viewer',2)]:
  subprocess.run(['php','-d','session.save_path='+str(sessions),'-r',"session_id('"+sid+"');session_start();$_SESSION=['user_id'=>"+str(uid)+",'active_project_id'=>1,'tree_form_csrf'=>'test-form-token'];session_write_close();"],check=True)
 with (root/'http.log').open('w') as log:
  server=subprocess.Popen(['php','-d','session.save_path='+str(sessions),'-S','127.0.0.1:33080','-t',str(app)],stdout=log,stderr=log)
  def request(path,sid=None,data=None,token=None):
   headers={}
   if sid:headers['Cookie']='PHPSESSID='+sid
   if data is not None:headers['Content-Type']='application/json'
   if token:headers['X-CSRF-Token']=token
   q=urllib.request.Request('http://127.0.0.1:33080/map/public/api/'+path,data=json.dumps(data).encode() if data is not None else None,headers=headers)
   try:
    with urllib.request.urlopen(q) as response:return response.status,json.load(response)
   except urllib.error.HTTPError as e:return e.code,json.load(e)
  try:
   for _ in range(50):
    try:request('tree_measurements.php?tree_id=1');break
    except urllib.error.URLError:time.sleep(.1)
   assert request('tree_measurements.php?tree_id=1')[0]==401
   payload=dict(tree_id=1,measurement_type='dbh',value_cm=12,measured_at='2026-11-08 10:00:00',note='test')
   assert request('save_tree_measurement.php',data=payload)[0]==401
   assert request('save_tree_measurement.php','viewer',payload)[0]==403
   assert request('save_tree_measurement.php','editor',payload)[0]==403
   status,body=request('tree_measurements.php?tree_id=2','editor');assert status==200 and body['measurements']==[]
   token=body['csrf_token'];payload['tree_id']=2
   assert request('save_tree_measurement.php','editor',payload,token)[0]==404
   payload['tree_id']=1
   assert request('save_tree_measurement.php','editor',payload,token)[0]==200
   status,body=request('tree_measurements.php?tree_id=1','editor');assert status==200 and len(body['measurements'])==1 and body['measurements'][0]['created_by']==1
   def upload(filename,content,sid='editor',token='test-form-token',treeid='0'):
    boundary='svm-test-boundary'
    fields={k:'' for k in json.loads((app/'map/public/project_tools/tree_fields.json').read_text())}
    fields.update(vitality='1',tree_health='1',stability='1',operational_safety='1',ecological_value='1',project_id='1',tree_form_token=token,usr_id='99',id=treeid)
    fields['photo_order[]']=filename
    chunks=[]
    for k,v in fields.items():
     chunks.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+k+'"\r\n\r\n'+v+'\r\n').encode())
    chunks.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="photos[]"; filename="'+filename+'"\r\nContent-Type: image/png\r\n\r\n').encode()+content+b'\r\n')
    chunks.append(('--'+boundary+'--\r\n').encode())
    headers={'Content-Type':'multipart/form-data; boundary='+boundary,'X-Requested-With':'TreeApp'}
    if sid:headers['Cookie']='PHPSESSID='+sid
    q=urllib.request.Request('http://127.0.0.1:33080/map/public/tree_save.php',data=b''.join(chunks),headers=headers)
    try:
     with urllib.request.urlopen(q) as response:return response.status,response.read()
    except urllib.error.HTTPError as e:return e.code,e.read()
   before=set((app/'map/public/uploads').iterdir())
   assert upload('attack.php',b'<?php echo "unexpected"; ?>')[0]==422
   assert upload('fake.png',b'not an image')[0]==422
   assert upload('attack.php',b'<?php ?>',sid='viewer')[0]==403
   assert upload('attack.php',b'<?php ?>',token='wrong')[0]==403
   assert set((app/'map/public/uploads').iterdir())==before
   png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6MZ0AAAAASUVORK5CYII=')
   assert upload('cross-project.png',png,treeid='2')[0]==404
   assert set((app/'map/public/uploads').iterdir())==before
   status,body=upload('original.php',png)
   assert status==200 and json.loads(body)['success'],(status,body,(root/'http.log').read_text()[-4000:])
   added=set((app/'map/public/uploads').iterdir())-before
   assert len(added)==1 and next(iter(added)).suffix=='.png'
   assert next(iter(added)).read_bytes()==png
   def post_form(endpoint,fields,sid='editor'):
    q=urllib.request.Request('http://127.0.0.1:33080/map/public/'+endpoint,data=urllib.parse.urlencode(fields).encode(),headers={'Cookie':'PHPSESSID='+sid})
    try:
     with urllib.request.urlopen(q) as r:return r.status
    except urllib.error.HTTPError as e:return e.code
   assert post_form('tree_history_save.php',{'tree_id':1})==403
   assert post_form('tree_history_save.php',{'tree_id':2,'tree_form_token':'test-form-token'})==404
   assert post_form('tree_history_save.php',{'tree_id':1,'tree_form_token':'test-form-token'},'viewer')==403
   assert post_form('tree_delete.php',{'id':1})==403
   assert post_form('tree_delete.php',{'id':2,'tree_form_token':'test-form-token'})==404
   assert post_form('tree_delete.php',{'id':1,'tree_form_token':'test-form-token'},'viewer')==403
   for endpoint in ['tree_remove.php','tree_restore.php','share_tree.php?id=1']:
    assert post_form(endpoint,{'id':1,'action':'revoke'})==403
    assert post_form(endpoint,{'id':1,'action':'revoke','tree_form_token':'test-form-token'},'viewer')==403
   def sql(statement):
    return subprocess.check_output(['mariadb','--no-defaults','--protocol=tcp','-h127.0.0.1','-P'+port,'-uroot','-N','valuation_fresh','-e',statement]).decode().strip()
   share_token='a'*64
   sql("INSERT INTO tree_shares(tree_id,token_hash) VALUES(1,'"+hashlib.sha256(share_token.encode()).hexdigest()+"'); INSERT INTO trees_photo(tree_id,url,note_photo,status) VALUES(1,'uploads/active.jpg','VISIBLE-PHOTO','active'),(1,'uploads/archived.jpg','ARCHIVED-SECRET','archived');")
   url='http://127.0.0.1:33080/map/public/public_tree.php?token='+share_token
   with urllib.request.urlopen(url) as response:
    html=response.read().decode();assert 'VISIBLE-PHOTO' in html and 'ARCHIVED-SECRET' not in html and 'archived.jpg' not in html
    assert response.headers['Cache-Control']=='no-store' and response.headers['Referrer-Policy']=='no-referrer'
   sql('UPDATE tree_shares SET active=0')
   try:urllib.request.urlopen(url);raise AssertionError('Revoked share readable')
   except urllib.error.HTTPError as e:assert e.code==404
   sql("UPDATE projects SET owner_id=NULL WHERE id=1; INSERT INTO project_users VALUES(1,1,'viewer',NOW());")
   # Simulate an already logged-in former owner; permissions must be refreshed for each write.
   for endpoint in ['tree_delete.php','tree_remove.php','tree_restore.php']:
    subprocess.run(['php','-d','session.save_path='+str(sessions),'-r',"session_id('editor');session_start();$_SESSION['active_project_role']='owner';session_write_close();"],check=True)
    assert post_form(endpoint,{'id':1,'tree_form_token':'test-form-token'})==403
   assert sql('SELECT COUNT(*) FROM trees WHERE id=1 AND status=\'active\'')=='1'
   print('PASS sharing/lifecycle: CSRF and revoked role denied; public gallery excludes archive; revoked share unreadable')
   print('PASS history/delete HTTP: CSRF, foreign project and viewer requests denied')
   print('PASS legacy photo HTTP: scripts/spoofed images rejected; viewer/CSRF denied; real image stored under random .png name')
   print('PASS measurement HTTP: anonymous/viewer/CSRF/cross-project denied; authorized read/write and author retained')
  finally:server.terminate();server.wait(timeout=10)
