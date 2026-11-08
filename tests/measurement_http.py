import pathlib,tempfile,shutil,subprocess,sys,json,time,urllib.request,urllib.error
repo=pathlib.Path(__file__).resolve().parent.parent;port=sys.argv[1]
subprocess.run(['mariadb','--no-defaults','--protocol=tcp','-h127.0.0.1','-P'+port,'-uroot','valuation_fresh','-e',"INSERT INTO users(id,username,password,active) VALUES(1,'editor','unused',1),(2,'viewer','unused',1); INSERT INTO projects(id,name,owner_id) VALUES(1,'A',1),(2,'B',NULL); INSERT INTO project_users VALUES(1,2,'viewer',NOW()); INSERT INTO trees(id,project_id) VALUES(1,1),(2,2); INSERT INTO tree_measurements(tree_id,measurement_type,value_cm) VALUES(2,'dbh',44);"],check=True)
with tempfile.TemporaryDirectory() as td:
 root=pathlib.Path(td);app=root/'app';shutil.copytree(repo,app);sessions=root/'sessions';sessions.mkdir()
 (app/'private/config.php').write_text("<?php return ['database'=>['host'=>'127.0.0.1','port'=>"+port+",'user'=>'root','password'=>'','name'=>'valuation_fresh']];")
 for sid,uid in [('editor',1),('viewer',2)]:
  subprocess.run(['php','-d','session.save_path='+str(sessions),'-r',"session_id('"+sid+"');session_start();$_SESSION=['user_id'=>"+str(uid)+",'active_project_id'=>1];session_write_close();"],check=True)
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
   print('PASS measurement HTTP: anonymous/viewer/CSRF/cross-project denied; authorized read/write and author retained')
  finally:server.terminate();server.wait(timeout=10)
