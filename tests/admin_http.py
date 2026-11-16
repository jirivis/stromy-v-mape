import pathlib,tempfile,shutil,subprocess,sys,time,http.client,urllib.parse,re,hashlib
repo=pathlib.Path(__file__).resolve().parent.parent;port=sys.argv[1]
def sql(q):return subprocess.check_output(['mariadb','--no-defaults','--protocol=tcp','-h127.0.0.1','-P'+port,'-uroot','-N','valuation_fresh','-e',q]).decode().strip()
sql("INSERT INTO users(id,username,email,password,role,is_super_admin,active) VALUES(30,'super','s@example.invalid','unused','admin',1,1),(31,'owner','o@example.invalid','unused','client',0,1),(32,'reader','r@example.invalid','unused','viewer',0,1),(33,'invited','i@example.invalid','unused','client',0,0),(34,'globaladmin','ga@example.invalid','unused','admin',0,1); INSERT INTO projects(id,name,owner_id) VALUES(30,'own',31),(31,'foreign',30); INSERT INTO project_users VALUES(30,32,'viewer',NOW());")
with tempfile.TemporaryDirectory() as td:
 root=pathlib.Path(td);app=root/'app';shutil.copytree(repo,app);sessions=root/'sessions';sessions.mkdir()
 (app/'private/config.php').write_text("<?php return ['app'=>['url'=>'http://127.0.0.1:33084'],'database'=>['host'=>'127.0.0.1','port'=>"+port+",'user'=>'root','password'=>'','name'=>'valuation_fresh']];")
 for sid,uid in [('super',30),('owner',31),('reader',32),('guest',0),('globaladmin',34)]:
  subprocess.run(['php','-d','session.save_path='+str(sessions),'-r',"session_id('"+sid+"');session_start();$_SESSION=['user_id'=>"+str(uid)+",'password_version'=>hash('sha256','unused'),'auth_form_token'=>'admin-test-token'];session_write_close();"],check=True)
 def request(path,sid='reader',data=None):
  c=http.client.HTTPConnection('127.0.0.1',33084);headers={'Cookie':'PHPSESSID='+sid}
  if data is not None:headers['Content-Type']='application/x-www-form-urlencoded'
  c.request('POST' if data is not None else 'GET',path,urllib.parse.urlencode(data) if data is not None else None,headers)
  r=c.getresponse();body=r.read().decode();status=r.status;c.close();return status,body
 with (root/'http.log').open('w') as log:
  server=subprocess.Popen(['php','-d','session.save_path='+str(sessions),'-S','127.0.0.1:33084','-t',str(app)],stdout=log,stderr=log)
  try:
   for _ in range(80):
    try:request('/projects.php');break
    except ConnectionRefusedError:time.sleep(.1)
   routes=['user_new.php','user_edit.php?id=32','project_new.php','project_edit.php?id=30','project_users.php?id=30','client_new.php','client_edit.php?id=1','switch_project.php']
   for route in routes:assert request('/'+route,'super',{})[0]==403,route
   valid={'auth_form_token':'admin-test-token'}
   for route in routes[:-1]:assert request('/'+route,'reader',valid)[0]==403,route
   assert request('/project_edit.php?id=31','owner',valid)[0]==403
   assert request('/project_users.php?id=31','owner',valid)[0]==403
   status,body=request('/project_edit.php?id=30','owner',dict(valid,name='changed',client_id='0',visibility='private',owner_id='30'))
   assert status==200,(status,body)
   assert sql('SELECT CONCAT(name,":",owner_id) FROM projects WHERE id=30')=='changed:31'
   assert request('/project_users.php?id=30','owner',dict(valid,action='add',user_id='32',project_role='editor'))[0]==200
   assert sql('SELECT project_role FROM project_users WHERE project_id=30 AND user_id=32')=='editor'
   # A global admin without project membership cannot manage or take over a project.
   for route in ['/project_edit.php?id=30','/project_users.php?id=30']:
    assert request(route,'globaladmin')[0]==403,route
    assert request(route,'globaladmin',dict(valid,action='add',user_id='34',project_role='admin',name='hijacked',owner_id='34'))[0]==403,route
   assert sql('SELECT owner_id FROM projects WHERE id=30')=='31'
   assert sql('SELECT COUNT(*) FROM project_users WHERE project_id=30 AND user_id=34')=='0'
   # Project admins may manage membership but cannot transfer ownership.
   assert request('/project_users.php?id=30','owner',dict(valid,action='add',user_id='34',project_role='admin'))[0]==200
   assert request('/project_users.php?id=30','globaladmin',dict(valid,action='change_role',user_id='32',project_role='viewer'))[0]==200
   assert sql('SELECT project_role FROM project_users WHERE project_id=30 AND user_id=32')=='viewer'
   assert request('/project_edit.php?id=30','globaladmin',dict(valid,name='changed',client_id='0',visibility='private',owner_id='34'))[0]==200
   assert sql('SELECT owner_id FROM projects WHERE id=30')=='31'
   # Neither removing the owner nor changing their role may succeed.
   for action in ['remove','change_role']:
    request('/project_users.php?id=30','globaladmin',dict(valid,action=action,user_id='31',project_role='viewer'))
    assert sql('SELECT project_role FROM project_users WHERE project_id=30 AND user_id=31')=='owner'
   # A demotion and then removal take effect in the existing session, without a new login.
   request('/project_users.php?id=30','owner',dict(valid,action='change_role',user_id='34',project_role='viewer'))
   assert request('/project_users.php?id=30','globaladmin',dict(valid,action='add',user_id='34',project_role='admin'))[0]==403
   assert request('/project_edit.php?id=30','globaladmin')[0]==403
   request('/project_users.php?id=30','owner',dict(valid,action='remove',user_id='34'))
   assert sql('SELECT COUNT(*) FROM project_users WHERE project_id=30 AND user_id=34')=='0'
   assert request('/project_users.php?id=30','globaladmin')[0]==403
   assert request('/project_edit.php?id=31','super')[0]==200
   assert request('/project_users.php?id=31','super')[0]==200
   invite='d'*64;reset='e'*64
   sql("INSERT INTO user_invitations(user_id,token_hash,expires_at) VALUES(33,'"+hashlib.sha256(invite.encode()).hexdigest()+"',NOW()+INTERVAL 2 DAY); INSERT INTO password_reset_tokens(user_id,token_hash,expires_at) VALUES(33,'"+hashlib.sha256(reset.encode()).hexdigest()+"',NOW()+INTERVAL 2 DAY);")
   url='/accept_invitation.php?token='+invite
   assert request(url,'guest',{'password':'Invitation-pass-123','password_again':'Invitation-pass-123'})[0]==403
   request(url,'guest',dict(valid,password='x'*73,password_again='x'*73))
   assert sql('SELECT active FROM users WHERE id=33')=='0'
   request(url,'guest',dict(valid,password='Invitation-pass-123',password_again='Invitation-pass-123'))
   assert sql('SELECT active FROM users WHERE id=33')=='1'
   first_hash=sql('SELECT password FROM users WHERE id=33')
   request(url,'guest',dict(valid,password='Different-pass-456',password_again='Different-pass-456'))
   assert sql('SELECT password FROM users WHERE id=33')==first_hash
   # A still-pending invitation must not re-enable an account disabled by the administrator.
   sql("INSERT INTO user_invitations(user_id,token_hash,expires_at) VALUES(33,'"+hashlib.sha256(b'f'*64).hexdigest()+"',NOW()+INTERVAL 2 DAY)")
   status,body=request('/user_edit.php?id=33','super',dict(valid,action='save',username='invited',email='i@example.invalid',role='client'))
   assert status==200,(status,body)
   assert sql('SELECT active FROM users WHERE id=33')=='0'
   assert sql('SELECT COUNT(*) FROM user_invitations WHERE user_id=33 AND used_at IS NULL')=='0'
   assert sql('SELECT COUNT(*) FROM password_reset_tokens WHERE user_id=33 AND used_at IS NULL')=='0'
   print('PASS admin HTTP: CSRF/role/project boundaries; own-project edits; ownership retained; invitations one-use; deactivation revokes links')
  except Exception:
   print((root/'http.log').read_text()[-3000:]);raise
  finally:server.terminate();server.wait(timeout=10)
