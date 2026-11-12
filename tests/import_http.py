"""Real HTTP import regressions on an isolated database; no production data."""
import hashlib,pathlib,tempfile,shutil,subprocess,sys,time,http.client,json,urllib.parse,zipfile,io
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
   def export(data,sid='importer'):
    c=http.client.HTTPConnection('127.0.0.1',33085)
    c.request('POST','/map/public/project_tools/export.php',urllib.parse.urlencode(data),{'Content-Type':'application/x-www-form-urlencoded','Cookie':'PHPSESSID='+sid})
    response=c.getresponse();body=response.read();status=response.status;c.close();return status,body
   base={'token':'import-token','project_id':'40','scope':'selection','trees':'1','areas':'1'}
   for bad in ['null','false','[]','{}','{"trees":[{}],"areas":[]}','{"trees":[true],"areas":[]}']:
    assert export(dict(base,selection=bad))[0]==422,bad
   assert export(dict(base,scope='unknown'))[0]==422
   assert export(dict(base,selection='{"trees":[],"areas":[]}'))[0]==422
   own=sql('SELECT id FROM trees WHERE project_id=40 AND usr_id=401')
   sql("INSERT INTO trees(project_id,usr_id,species) VALUES(41,999,'PRIVATE FOREIGN SPECIES')")
   foreign=sql('SELECT id FROM trees WHERE project_id=41 AND usr_id=999')
   selection=json.dumps({'trees':[own,foreign],'areas':[]})
   assert export(dict(base,selection=selection),'importviewer')[0]==403
   status,body=export(dict(base,selection=selection));assert status==200,(status,body[:500])
   with zipfile.ZipFile(io.BytesIO(body)) as archive:
    assert 'Stromy: 1' in archive.read('CTI_ME.txt').decode()
    with zipfile.ZipFile(io.BytesIO(archive.read('tabulky.xlsx'))) as workbook:
     xml=b''.join(workbook.read(n) for n in workbook.namelist() if n.endswith('.xml'))
     assert b'Import test' in xml and b'PRIVATE FOREIGN SPECIES' not in xml
   # Photo import uses an explicit server-side plan and individual authenticated uploads.
   image_path=root/'sample.jpg'
   subprocess.run(['php','-r',"$im=imagecreatetruecolor(1600,1200);imagejpeg($im,$argv[1],90);",str(image_path)],check=True)
   image=image_path.read_bytes();digest=hashlib.sha256(image).hexdigest()
   photo_row={'file':'20261112_153341.jpg','code':'401','note':'Koruna – detail','sha256':digest}
   assert request('import_photos_api.php','preview',{'kind':'trees','rows':[photo_row]},sid='importviewer')[0]==403
   assert request('import_photos_api.php','preview',{'kind':'trees','rows':[dict(photo_row,code='999')]})[0]==422
   assert request('import_photos_api.php','preview',{'kind':'trees','rows':[photo_row,photo_row]})[0]==422
   status,plan=request('import_photos_api.php','preview',{'kind':'trees','rows':[photo_row]});assert status==200,(status,plan)
   def photo_upload(plan,index=0,data=image):
    boundary='svmPhotoBoundary'
    fields={'plan':plan,'index':str(index)}
    body=b''.join(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+k+'"\r\n\r\n'+v+'\r\n').encode() for k,v in fields.items())
    body+=('--'+boundary+'\r\nContent-Disposition: form-data; name="photo"; filename="20261112_153341.jpg"\r\nContent-Type: image/jpeg\r\n\r\n').encode()+data+('\r\n--'+boundary+'--\r\n').encode()
    c=http.client.HTTPConnection('127.0.0.1',33085);c.request('POST','/map/public/project_tools/import_photos_api.php?action=upload',body,{'Content-Type':'multipart/form-data; boundary='+boundary,'Cookie':'PHPSESSID=importer','X-CSRF-Token':'import-token','X-Project-Id':'40'})
    r=c.getresponse();status=r.status;body=r.read();c.close();return status,json.loads(body)
   assert photo_upload(plan['token'],data=b'not the previewed file')[0]==422
   before=int(sql('SELECT COUNT(*) FROM trees_photo WHERE tree_id='+own))
   assert photo_upload(plan['token'])[0]==200
   assert photo_upload(plan['token'])[1]['already_imported'] is True
   assert int(sql('SELECT COUNT(*) FROM trees_photo WHERE tree_id='+own))==before+1
   photo_id=sql('SELECT MAX(id) FROM trees_photo WHERE tree_id='+own)
   def get_photo(path,cookie='PHPSESSID=importer'):
    c=http.client.HTTPConnection('127.0.0.1',33085);c.request('GET',path,headers={'Cookie':cookie});r=c.getresponse();data=r.read();status=r.status;c.close();return status,data
   endpoint='/map/public/photo.php?photo='+photo_id
   assert get_photo(endpoint)[1]==image
   status,thumb=get_photo(endpoint+'&thumb=1');assert status==200 and len(thumb)<len(image),(status,len(thumb),len(image))
   assert get_photo(endpoint+'&thumb=1',cookie='')[0]==404
   assert get_photo('/map/public/project_tools/card_photo.php?kind=trees&id='+photo_id)[1]==image
   for captions in ['0','1']:
    status,body=export(dict(base,selection=json.dumps({'trees':[own],'areas':[]}),photos='1',photo_captions=captions,photos_flat='1'))
    assert status==200,(status,body[:500])
    with zipfile.ZipFile(io.BytesIO(body)) as archive:
     assert archive.testzip() is None
     names=[n for n in archive.namelist() if n.startswith('fotografie/')];assert len(names)==1 and names[0].startswith('fotografie/strom401_20261112_153341_01'),names
     exported=archive.read(names[0]);assert (exported==image)==(captions=='0')
     assert names[0].encode() in archive.read('prirazeni_fotografii_stromy.csv')
   status,plan=request('import_photos_api.php','preview',{'kind':'areas','rows':[photo_row]});assert status==200,(status,plan)
   assert photo_upload(plan['token'])[0]==200
   area=sql('SELECT id FROM svm_areas WHERE project_id=40 AND code=401')
   assert sql('SELECT COUNT(*) FROM svm_area_photos WHERE area_id='+area)=='1'
   status,body=export(dict(base,selection=json.dumps({'trees':[],'areas':[area]}),photos='1',photo_captions='1',photos_flat='1'));assert status==200,(status,body[:500])
   with zipfile.ZipFile(io.BytesIO(body)) as archive:
    assert archive.testzip() is None
    assert 'fotografie/plocha401_20261112_153341_01.jpg' in archive.namelist()
   status,body=export(dict(base,selection='{}',frame='1'));assert status==422 and b'svmExportError' in body
   print('PASS photo import: role/project scope, ambiguous/duplicate plans, content fingerprint, idempotent retry; caption and original ZIP downloads')
   print('PASS export HTTP: invalid/null selection rejected; selected ZIP contains only authorized selected tree')
   print('PASS import HTTP: tree/area preview, CSRF, viewer, project switch, revoked role, single-use commit, conflicting batch leaves no partial writes')
  except Exception:
   print((root/'http.log').read_text()[-3000:]);raise
  finally:server.terminate();server.wait(timeout=10)
