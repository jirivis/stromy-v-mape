import json,pathlib,tempfile,shutil,subprocess,sys,time,http.client,urllib.parse,re,hashlib
repo=pathlib.Path(__file__).resolve().parent.parent;port=sys.argv[1]
def sql(q):return subprocess.check_output(['mariadb','--no-defaults','--protocol=tcp','-h127.0.0.1','-P'+port,'-uroot','-N','valuation_fresh','-e',q]).decode().strip()
hash1=subprocess.check_output(['php','-r',"echo password_hash('Original-password-123',PASSWORD_DEFAULT);"]).decode()
sql("INSERT INTO users(id,username,password,active) VALUES(20,'auth-test','"+hash1+"',1); INSERT INTO project_users VALUES(1,20,'viewer',NOW());")
with tempfile.TemporaryDirectory() as td:
 root=pathlib.Path(td);app=root/'app';shutil.copytree(repo,app);sessions=root/'sessions';sessions.mkdir()
 (app/'private/config.php').write_text("<?php return ['app'=>['url'=>'http://127.0.0.1:33083'],'database'=>['host'=>'127.0.0.1','port'=>"+port+",'user'=>'root','password'=>'','name'=>'valuation_fresh']];")
 class Browser:
  def __init__(self):self.cookie=''
  def request(self,path,data=None):
   c=http.client.HTTPConnection('127.0.0.1',33083);headers={'Cookie':self.cookie}
   if data is not None:headers['Content-Type']='application/x-www-form-urlencoded'
   c.request('POST' if data is not None else 'GET',path,urllib.parse.urlencode(data) if data is not None else None,headers)
   r=c.getresponse();body=r.read().decode();h=dict(r.getheaders());status=r.status;c.close()
   if 'Set-Cookie' in h:self.cookie=h['Set-Cookie'].split(';')[0]
   return status,body,h
 def token(body):return re.search(r'name="auth_form_token" value="([a-f0-9]+)"',body)[1]
 with (root/'http.log').open('w') as log:
  server=subprocess.Popen(['php','-d','session.save_path='+str(sessions),'-S','127.0.0.1:33083','-t',str(app)],stdout=log,stderr=log)
  try:
   login=Browser()
   for _ in range(80):
    try:status,body,h=login.request('/login.php');break
    except ConnectionRefusedError:time.sleep(.1)
   assert status==200,(status,body)
   csrf=token(body);old_cookie=login.cookie
   assert 'HttpOnly' in h['Set-Cookie'] and 'SameSite=Lax' in h['Set-Cookie']
   assert login.request('/login_process.php',{'username':'auth-test','password':'Original-password-123'})[0]==403
   assert login.request('/forgot_password.php',{'email':'test@example.invalid'})[0]==403
   status,body,h=login.request('/login_process.php',{'username':'auth-test','password':'Original-password-123','auth_form_token':csrf})
   assert status==302 and h['Location']=='/dashboard.php',(status,body)
   assert login.cookie!=old_cookie
   assert login.request('/map/public/api/tree_measurements.php?tree_id=1')[0]==200
   status,body,h=login.request('/map/public/project_tools/api.php?action=filter_fields')
   assert status==200,(status,body)
   catalog=json.loads(body);assert catalog['project_id']==1 and 'usr_id' in catalog['trees'] and 'area_m2' in catalog['areas']
   assert 'id' not in catalog['trees'] and 'rows' not in catalog and h['Cache-Control']=='no-store'
   status,body,h=login.request('/map/public/project_tools/api.php?action=rows&kind=trees')
   assert status==200 and json.loads(body)['table_fields']==catalog['trees'],(status,body)
   assert Browser().request('/map/public/project_tools/api.php?action=filter_fields')[0] in (401,403)
   sql("INSERT INTO trees(id,project_id,usr_id,species,height,lat_wgs84,lng_wgs84) VALUES(90001,1,902,'Filter fixture',NULL,50,14)")
   status,body,h=login.request('/map/public/api/trees_geojson.php')
   assert status==200,(status,body)
   feature=next(f for f in json.loads(body)['features'] if f['properties']['id']==90001)
   assert feature['properties']['filter_values']['height'] is None
   assert feature['properties']['filter_values']['species']=='Filter fixture'
   assert 'id' not in feature['properties']['filter_values'] and 'project_id' not in feature['properties']['filter_values']
   sql('DELETE FROM trees WHERE id=90001')
   print('PASS authenticated filter catalog matches table fields, excludes internal IDs, requires login')
   reset_token='b'*64
   sql("INSERT INTO password_reset_tokens(user_id,token_hash,expires_at) VALUES(20,'"+hashlib.sha256(reset_token.encode()).hexdigest()+"',NOW()+INTERVAL 2 DAY)")
   reset=Browser();url='/reset_password.php?token='+reset_token
   status,body,h=reset.request(url);assert status==200
   assert h['Referrer-Policy']=='no-referrer' and h['Cache-Control']=='no-store'
   reset_csrf=token(body)
   assert reset.request(url,{'password':'New-password-456','password_again':'New-password-456'})[0]==403
   def change(password):return reset.request(url,{'auth_form_token':reset_csrf,'password':password,'password_again':password})
   change('x'*73);assert sql('SELECT password FROM users WHERE id=20')==hash1
   status,body,h=change('New-password-456');assert status==200,(status,body)
   hash2=sql('SELECT password FROM users WHERE id=20');assert hash2!=hash1
   assert login.request('/map/public/api/tree_measurements.php?tree_id=1')[0]==403
   change('Other-password-789');assert sql('SELECT password FROM users WHERE id=20')==hash2
   expired='c'*64;sql("INSERT INTO password_reset_tokens(user_id,token_hash,expires_at) VALUES(20,'"+hashlib.sha256(expired.encode()).hexdigest()+"',NOW()-INTERVAL 2 DAY)")
   reset.request('/reset_password.php?token='+expired,{'auth_form_token':reset_csrf,'password':'Other-password-789','password_again':'Other-password-789'})
   assert sql('SELECT password FROM users WHERE id=20')==hash2
   for i in range(9):
    fresh=Browser();status,body,h=fresh.request('/login.php');csrf=token(body)
    status,body,h=fresh.request('/login_process.php',{'auth_form_token':csrf,'username':'unknown-rate-test','password':'invalid-password'})
    assert status==(302 if i<8 else 429),(i,status,body)
    if i==8:assert int(h['Retry-After'])>0
   # Unknown addresses exercise neutral responses without sending mail.
   for i in range(4):
    fresh=Browser();status,body,h=fresh.request('/forgot_password.php');csrf=token(body)
    status,body,h=fresh.request('/forgot_password.php',{'auth_form_token':csrf,'email':'nonexistent-rate@example.invalid'})
    assert status==(200 if i<3 else 429),(i,status,body)
    if i==3:assert int(h['Retry-After'])>0
   print('PASS rate HTTP: fresh sessions cannot bypass login/reset quotas; 429 includes Retry-After; no email sent')
   print('PASS auth HTTP: CSRF, cookie flags, session rotation, reset headers/length/one-use/expiry and old-login invalidation')
  except Exception:
   print((root/'http.log').read_text()[-3000:]);raise
  finally:server.terminate();server.wait(timeout=10)
