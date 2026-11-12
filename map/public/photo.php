<?php
declare(strict_types=1);
// Also used by Apache rewrites for legacy /photos and /uploads URLs.
require_once __DIR__.'/api/db.php';
require_once dirname(__DIR__,2).'/app/account.php';
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function photoNotFound():never{http_response_code(404);header('Content-Type: text/plain; charset=utf-8');exit('Fotografie není dostupná.');}
function localPhotoPath(string $url):?string{
 $u=parse_url($url);if($u===false||isset($u['user'])||isset($u['pass']))return null;
 if(isset($u['host'])&&strcasecmp($u['host'],(string)parse_url(svmUrl(),PHP_URL_HOST))!==0)return null;
 $p=rawurldecode($u['path']??'');if(str_starts_with($p,'/map/public/'))$p=substr($p,12);
 if(!preg_match('~^(photos|uploads)/[^\x00-\x1f]+$~D',$p)||str_contains($p,'\\'))return null;
 foreach(explode('/',$p) as $part)if($part===''||$part[0]==='.')return null;
 return $p;
}
try{
 $requested=null;$rows=[];
 if(isset($_GET['photo'])){
  $id=filter_var($_GET['photo'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id)photoNotFound();
  $s=$mysqli->prepare('SELECT p.url,p.status,t.id AS tree_id,t.project_id FROM trees_photo p JOIN trees t ON t.id=p.tree_id WHERE p.id=?');$s->execute([$id]);$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);
  if(!$rows)photoNotFound();$requested=localPhotoPath($rows[0]['url']);
 }else{
  $folder=(string)($_GET['folder']??'');$file=(string)($_GET['file']??'');
  $requested=localPhotoPath($folder.'/'.$file);if(!$requested)photoNotFound();
  $origin=parse_url(svmUrl());$host=$origin['host'].(isset($origin['port'])?':'.$origin['port']:'');$variants=[];
  foreach([$requested,implode('/',array_map('rawurlencode',explode('/',$requested)))] as $path){
   foreach([$path,'/map/public/'.$path,'https://'.$host.'/map/public/'.$path,'http://'.$host.'/map/public/'.$path] as $v)$variants[]=$v;
  }
  $variants=array_values(array_unique($variants));$marks=implode(',',array_fill(0,count($variants),'?'));
  $s=$mysqli->prepare("SELECT p.url,p.status,t.id AS tree_id,t.project_id FROM trees_photo p JOIN trees t ON t.id=p.tree_id WHERE p.url IN ($marks) UNION ALL SELECT t.photo_url AS url,'main' AS status,t.id AS tree_id,t.project_id FROM trees t WHERE t.photo_url IN ($marks)");
  $s->execute(array_merge($variants,$variants));$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);
 }
 if(!$requested)photoNotFound();
 // Do not create a session for anonymous image requests.
 $account=null;if(isset($_COOKIE[session_name()])){if(session_status()!==PHP_SESSION_ACTIVE)session_start();$account=svmLoadAccount($mysqli,(int)($_SESSION['user_id']??0));session_write_close();}
 $token=(string)($_GET['token']??'');$tokenHash=preg_match('/^[a-f0-9]{64}$/D',$token)?hash('sha256',$token):null;
 $allowed=false;
 foreach($rows as $row){
  if(localPhotoPath($row['url'])!==$requested)continue;
  if($account){
   $s=$mysqli->prepare('SELECT p.id FROM projects p WHERE p.id=? AND (?=1 OR p.owner_id=? OR EXISTS (SELECT 1 FROM project_users pu WHERE pu.project_id=p.id AND pu.user_id=?))');
   $s->execute([(int)$row['project_id'],(int)$account['is_super_admin'],(int)$account['id'],(int)$account['id']]);if($s->get_result()->fetch_assoc()){$allowed=true;break;}
  }
  // A share permits only current photos of that exact tree, never its archive.
  if($tokenHash&&$row['status']==='active'){
   $s=$mysqli->prepare('SELECT id FROM tree_shares WHERE tree_id=? AND token_hash=? AND active=1 AND (expires_at IS NULL OR expires_at>NOW()) LIMIT 1');$s->execute([(int)$row['tree_id'],$tokenHash]);
   if($s->get_result()->fetch_assoc()){$allowed=true;break;}
  }
 }
 if(!$allowed)photoNotFound();
 $base=realpath(__DIR__.'/'.explode('/',$requested)[0]);$path=realpath(__DIR__.'/'.$requested);
 if(!$base||!$path||!str_starts_with($path,$base.DIRECTORY_SEPARATOR)||!is_file($path))photoNotFound();
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);if(!in_array($mime,['image/jpeg','image/png','image/webp','image/gif'],true))photoNotFound();
 if(($_GET['thumb']??'')==='1'&&filesize($path)<=10*1024*1024){require_once dirname(__DIR__,2).'/app/photo_preview.php';$preview=svmPhotoPreview((string)file_get_contents($path));if($preview){$path=$preview;$mime='image/jpeg';}}
 header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));readfile($path);
}catch(Throwable $e){error_log('Photo authorization failed: '.get_class($e));photoNotFound();}
