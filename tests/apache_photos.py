"""Exercise real Apache .htaccess routes against the running PHP test server."""
import pathlib,subprocess,urllib.request,urllib.error,time,pwd,grp,os

def check_routes(root, app, png, token):
 root.chmod(0o755)
 modules=['mpm_event','authz_core','authz_host','rewrite','proxy','proxy_http','mime']
 config=root/'apache.conf'
 config.write_text('\n'.join([
  'ServerRoot "'+str(root)+'"','ServerName localhost','Listen 127.0.0.1:33081',
  'PidFile "'+str(root/'apache.pid')+'"','ErrorLog "'+str(root/'apache.log')+'"',
  'User '+pwd.getpwuid(os.getuid()).pw_name,'Group '+grp.getgrgid(os.getgid()).gr_name,
  *['LoadModule '+m+'_module /usr/lib/apache2/modules/mod_'+m+'.so' for m in modules],
  'TypesConfig /etc/mime.types','DocumentRoot "'+str(app)+'"',
  '<Directory "'+str(app)+'">','Require all granted','AllowOverride All','Options FollowSymLinks','</Directory>',
  '<Directory "'+str(app/'map/public/uploads')+'">','AllowOverride FileInfo AuthConfig Options=Indexes','</Directory>',
  'ProxyPass /map/public/photo.php http://127.0.0.1:33080/map/public/photo.php',
 ]))
 server=subprocess.Popen(['apache2','-f',str(config),'-DFOREGROUND'],stdout=subprocess.DEVNULL,stderr=subprocess.PIPE)
 def get(path,cookie=None):
  req=urllib.request.Request('http://127.0.0.1:33081'+path,headers={'Cookie':cookie} if cookie else {})
  try:
   with urllib.request.urlopen(req) as r:return r.status,r.read()
  except urllib.error.HTTPError as e:return e.code,e.read()
 try:
  for _ in range(100):
   if server.poll() is not None:raise AssertionError(server.stderr.read().decode())
   try:get('/map/public/uploads/active.jpg');break
   except urllib.error.URLError:time.sleep(.1)
  assert get('/map/public/uploads/active.jpg')[0]==404
  assert get('/map/public/uploads/active.jpg','PHPSESSID=editor')==(200,png)
  assert get('/map/public/uploads/active.jpg?token='+token)==(200,png)
  assert get('/map/public/uploads/archived.jpg?token='+token)[0]==404
  assert get('/map/public/uploads/foreign.jpg','PHPSESSID=editor')[0]==404
  assert get('/map/public/uploads/')[0]==403
  assert get('/map/public/uploads/evil.php')[0] in (403,404)
  assert get('/map/public/uploads/.htaccess')[0] in (403,404)
  print('PASS Apache routes: legacy image URL requires access; cookie and share token preserved; archive/foreign/config denied')
 except Exception:
  if (root/'apache.log').exists():print((root/'apache.log').read_text()[-5000:])
  raise
 finally:
  server.terminate();server.wait(timeout=10)
