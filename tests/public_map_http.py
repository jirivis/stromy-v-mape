"""Anonymous public map and authenticated publication boundary, isolated database."""
import pathlib,tempfile,shutil,subprocess,sys,time,http.client,json,urllib.parse
repo=pathlib.Path(__file__).resolve().parent.parent;port=sys.argv[1]
def sql(q):return subprocess.check_output(['mariadb','--no-defaults','--protocol=tcp','-h127.0.0.1','-P'+port,'-uroot','-N','valuation_fresh','-e',q]).decode().strip()
sql("INSERT INTO users(id,username,password,active) VALUES(60,'publisher','unused',1),(61,'publiceditor','unused',1),(62,'publicviewer','unused',1); INSERT INTO projects(id,name,owner_id,visibility) VALUES(60,'Obec',60,'public'),(61,'Private',60,'private'); INSERT INTO project_users VALUES(60,61,'editor',NOW()),(60,62,'viewer',NOW()); INSERT INTO trees(project_id,usr_id,species,note_tree,note_public,lat_wgs84,lng_wgs84) VALUES(60,7,'Quercus robur','NEVER_PUBLIC','Public text',50,14),(60,8,'PRIVATE_TREE','SECRET',NULL,50.1,14.1),(61,9,'OTHER_PROJECT',NULL,NULL,49,15);")
obj=sql('SELECT id FROM trees WHERE project_id=60 AND usr_id=7');foreign=sql('SELECT id FROM trees WHERE project_id=61 LIMIT 1')
geom={'type':'Polygon','coordinates':[[[14,50],[14.001,50],[14.001,50.001],[14,50]]]}
sql("INSERT INTO svm_areas(project_id,code,geometry_json,attributes_json,area_m2) VALUES(60,'P1','"+json.dumps(geom)+"','{\"name\":\"Public area\",\"note\":\"AREA_SECRET\"}',10)")
area=sql('SELECT id FROM svm_areas WHERE project_id=60')
with tempfile.TemporaryDirectory() as td:
 root=pathlib.Path(td);app=root/'app';shutil.copytree(repo,app);sessions=root/'sessions';sessions.mkdir()
 (app/'private/config.php').write_text("<?php return ['app'=>['url'=>'http://127.0.0.1:33086'],'database'=>['host'=>'127.0.0.1','port'=>"+port+",'user'=>'root','password'=>'','name'=>'valuation_fresh']];")
 for name,uid in [('publisher',60),('publiceditor',61),('publicviewer',62)]:
  subprocess.run(['php','-d','session.save_path='+str(sessions),'-r',"session_id('"+name+"');session_start();$_SESSION['user_id']="+str(uid)+";$_SESSION['active_project_id']=60;$_SESSION['password_version']=hash('sha256','unused');$_SESSION['project_tools_csrf']='public-test';session_write_close();"],check=True)
 subprocess.run(['php','-r',"$im=imagecreatetruecolor(20,20);imagejpeg($im,$argv[1]);",str(app/'map/public/uploads/public-test.jpg')],check=True)
 sql("INSERT INTO trees_photo(tree_id,url,note_photo) VALUES("+obj+",'uploads/public-test.jpg','CAPTION_SECRET')")
 def request(path,body=None,cookie='',csrf='public-test'):
  c=http.client.HTTPConnection('127.0.0.1',33086);headers={'Cookie':cookie,'Content-Type':'application/json','X-CSRF-Token':csrf,'X-Project-Id':'60'};c.request('POST' if body is not None else 'GET',path,json.dumps(body) if body is not None else None,headers);r=c.getresponse();b=r.read();status=r.status;newcookie=(r.getheader('Set-Cookie') or '').split(';')[0];c.close()
  try:b=json.loads(b)
  except (ValueError,UnicodeDecodeError):pass
  return status,b,newcookie
 def admin(action,body=None,who='publisher',csrf='public-test'):return request('/map/public/project_tools/public_admin_api.php?action='+action,body,'PHPSESSID='+who,csrf)[:2]
 def pub(action='map',object='',body=None,cookie='',project=None):return request('/map/public/community/api.php?'+urllib.parse.urlencode({'p':project or token,'action':action,'object':object}),body,cookie)
 with (root/'http.log').open('w') as log:
  server=subprocess.Popen(['php','-d','session.save_path='+str(sessions),'-S','127.0.0.1:33086','-t',str(app)],stdout=log,stderr=log)
  try:
   for _ in range(80):
    try:admin('settings');break
    except ConnectionRefusedError:time.sleep(.1)
   token='0'*48;assert pub()[0]==404 # legacy projects.visibility public alone does nothing
   assert admin('settings',who='publiceditor')[0]==403;assert admin('settings',who='publicviewer')[0]==403
   settings={'version':0,'enabled':False,'fields':{'trees':['species','note_public'],'areas':['name']},'photos':False,'photo_notes':False,'reports':True,'email_notify':False}
   assert admin('settings',settings,csrf='bad')[0]==403
   assert admin('settings',dict(settings,fields={'trees':['note_tree'],'areas':[]}))[0]==422
   assert admin('settings',settings)[0]==200;token=admin('settings')[1]['settings']['token'];assert pub()[0]==404
   assert admin('publish',{'kind':'trees','ids':[obj],'enabled':True},who='publiceditor')[0]==403
   assert admin('publish',{'kind':'trees','ids':[obj,foreign],'enabled':True})[0]==409
   assert sql('SELECT COUNT(*) FROM svm_public_objects WHERE project_id=60')=='0'
   assert admin('publish',{'kind':'trees','ids':[obj],'enabled':True})[0]==200
   assert admin('publish',{'kind':'areas','ids':[area],'enabled':True})[0]==200
   assert pub()[0]==404
   settings.update(version=1,enabled=True);assert admin('settings',settings)[0]==200
   status,data,_=pub();assert status==200 and len(data['features'])==2,data
   encoded=json.dumps(data);assert all(s not in encoded for s in ['PRIVATE_TREE','OTHER_PROJECT','NEVER_PUBLIC','object_id','project_id','AREA_SECRET','attributes_json','photo_url'])
   feature=next(f for f in data['features'] if f['properties']['kind']=='trees');ot=feature['properties']['token'];at=next(f['properties']['token'] for f in data['features'] if f['properties']['kind']=='areas')
   assert 'Public text' in json.dumps(feature['properties']['values'])
   status,detail,_=pub('detail',ot);assert status==200 and detail['photos']==[];assert 'Public text' in json.dumps(detail) and 'NEVER_PUBLIC' not in json.dumps(detail)
   assert 'AREA_SECRET' not in json.dumps(pub('detail',at)[1]);assert pub('detail',foreign)[0]==404
   settings.update(version=2,photos=True);assert admin('settings',settings)[0]==200
   photo=pub('detail',ot)[1]['photos'][0];assert photo['note']=='' and 'uploads' not in photo['url'];photo_url='/map/public/community/'+photo['url'];assert request(photo_url)[0]==200
   status,challenge,cookie=pub('challenge',ot);assert status==200 and cookie
   body={'token':challenge['token'],'message':'Větev visí nad chodníkem.','contact':'resident@example.test','website':''}
   assert pub('report',ot,body)[0]==403
   time.sleep(2.1)
   assert pub('report',ot,dict(body,website='spam'),cookie)[0]==422
   assert pub('report',ot,body,cookie)[0]==200
   assert pub('report',ot,body,cookie)[0]==403 # no replay
   assert 'resident@example.test' not in json.dumps(pub('detail',ot)[1]);assert pub('reports',ot)[0]==405
   assert admin('reports',who='publiceditor')[0]==403
   rows=admin('reports')[1]['rows'];assert len(rows)==1 and rows[0]['contact']=='resident@example.test';rid=rows[0]['id']
   assert admin('report_status',{'id':rid,'status':'working'})[0]==200
   assert admin('reports')[1]['rows'][0]['status']=='working'
   # IP quota survives a new anonymous cookie; raw IP is never stored.
   secret=__import__('hashlib').sha256(b'|valuation_fresh').hexdigest();bucket=__import__('hmac').new(secret.encode(),('ip|127.0.0.1|'+str(int(time.time())//3600)).encode(),'sha256').hexdigest();sql("UPDATE svm_public_rate SET hits=5 WHERE bucket='"+bucket+"'")
   _,c,cookie=pub('challenge',ot);time.sleep(2.1);assert pub('report',ot,dict(body,token=c['token']),cookie)[0]==429
   assert admin('publish',{'kind':'trees','ids':[obj],'enabled':False})[0]==200;assert pub('detail',ot)[0]==404;assert request(photo_url)[0]==404
   settings.update(version=3,enabled=False);assert admin('settings',settings)[0]==200;assert pub()[0]==404;assert pub('detail',at)[0]==404
   assert admin('delete_report',{'id':rid})[0]==200;assert admin('reports')[1]['rows']==[]
   # Repeating the additive migration preserves settings and existing rows.
   for _ in range(2):subprocess.run(['mariadb','--no-defaults','--protocol=tcp','-h127.0.0.1','-P'+port,'-uroot','valuation_fresh'],input=(repo/'database/migrations/08_public_map.sql').read_bytes(),check=True)
   assert sql('SELECT token FROM svm_public_maps WHERE project_id=60')==token
   print('PASS public map: double opt-in, explicit fields, no IDs/private notes, role/CSRF/project isolation, public photo revocation, moderated reports, rate limit and no replay')
  except Exception:
   print((root/'http.log').read_text()[-5000:]);raise
  finally:server.terminate();server.wait(timeout=10)
