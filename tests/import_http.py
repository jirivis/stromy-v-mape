"""Real HTTP import regressions on an isolated database; no production data."""
import pathlib,tempfile,shutil,subprocess,sys,time,http.client,json
repo=pathlib.Path(__file__).resolve().parent.parent;port=sys.argv[1]
def sql(q):return subprocess.check_output(['mariadb','--no-defaults','--protocol=tcp','-h127.0.0.1','-P'+port,'-uroot','-N','valuation_fresh','-e',q]).decode().strip()
sql("INSERT INTO users(id,username,password,active) VALUES(40,'importer','unused',1),(41,'importviewer','unused',1); INSERT INTO projects(id,name,owner_id) VALUES(40,'Import A',NULL),(41,'Import B',40); INSERT INTO project_users VALUES(40,40,'editor',NOW()),(40,41,'viewer',NOW());")
with tempfile.TemporaryDirectory() as td:
 root=pathlib.Path(td);app=root/'app';shutil.copytree(repo,app);sessions=root/'sessions';sessions.mkdir()
 (app/'private/config.php').write_text("<?php return ['app'=>['url'=>'http://127.0.0.1:33085'],'database'=>['host'=>'127.0.0.1','port'=>"+port+",'user'=>'root','password'=>'','name'=>'valuation_fresh']];")
 def session(sid,uid,project=40):
  subprocess.run(['php','-d','session.save_path='+str(sessions),'-r',"session_id('"+sid+"');session_start();$_SESSION['user_id']="+str(uid)+";$_SESSION['active_project_id']="+str(project)+";$_SESSION['password_version']=hash('sha256','unused');$_SESSION['project_tools_csrf']='import-token';session_write_close();"],check=True)
 session('importer',40);session('importviewer',41)
 def request(endpoint,action,data,sid='importer',token='import-token',project=40):
  c=http.client.HTTPConnection('127.0.0.1',33085);headers={'Content-Type':'application/json','Cookie':'PHPSESSID='+sid,'X-CSRF-Token':token,'X-Project-Id':str(project)}
  c.request('POST','/map/public/project_tools/'+endpoint+'?action='+action,json.dumps(data),headers);r=c.getresponse();body=r.read();status=r.status;c.close()
  return status,json.loads(body)
 with (root/'http.log').open('w') as log:
  server=subprocess.Popen(['php','-d','session.save_path='+str(sessions),'-S','127.0.0.1:33085','-t',str(app)],stdout=log,stderr=log)
  try:
   for _ in range(80):
    try:request('import_api.php','preview',{});break
    except ConnectionRefusedError:time.sleep(.1)
   geom={'type':'Polygon','coordinates':[[[14,50],[14.001,50],[14.001,50.001],[14,50]]]}
   for endpoint,table,row,field in [('import_api.php','trees',{'usr_id':401,'species':'Import test'},'usr_id'),('import_areas_api.php','svm_areas',{'code':'401','geometry':geom},'code')]:
    data={'mode':'create','rows':[row]}
    assert request(endpoint,'preview',data,token='wrong')[0]==403
    assert request(endpoint,'preview',data,sid='importviewer')[0]==403
    assert request(endpoint,'preview',data,project=41)[0]==409
    status,body=request(endpoint,'preview',data);assert status==200 and body['token'],body
    token=body['token'];assert sql('SELECT COUNT(*) FROM '+table+' WHERE project_id=40')=='0'
    session('importer',40,41)
    assert request(endpoint,'commit',{'token':token},project=41)[0]==409
    session('importer',40)
    sql("UPDATE project_users SET project_role='viewer' WHERE project_id=40 AND user_id=40")
    assert request(endpoint,'commit',{'token':token})[0]==403
    sql("UPDATE project_users SET project_role='editor' WHERE project_id=40 AND user_id=40")
    assert request(endpoint,'commit',{'token':token})[0]==200
    assert request(endpoint,'commit',{'token':token})[0]==409
    assert sql('SELECT COUNT(*) FROM '+table+' WHERE project_id=40')=='1'
    # Conflict in the second record prevents even the first record from being written.
    rows=[dict(row,**{field:402}),dict(row,**{field:403})]
    status,body=request(endpoint,'preview',{'mode':'create','rows':rows});assert status==200 and body['token'],body
    if table=='trees':sql('INSERT INTO trees(project_id,usr_id) VALUES(40,403)')
    else:sql("INSERT INTO svm_areas(project_id,code,geometry_json,attributes_json,area_m2) SELECT project_id,'403',geometry_json,attributes_json,area_m2 FROM svm_areas WHERE project_id=40 LIMIT 1")
    assert request(endpoint,'commit',{'token':body['token']})[0]==409
    assert sql('SELECT COUNT(*) FROM '+table+' WHERE project_id=40 AND '+field+"='402'")=='0'
   print('PASS import HTTP: tree/area preview, CSRF, viewer, project switch, revoked role, single-use commit, conflicting batch leaves no partial writes')
  except Exception:
   print((root/'http.log').read_text()[-3000:]);raise
  finally:server.terminate();server.wait(timeout=10)
