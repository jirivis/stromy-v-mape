<?php
require_once dirname(__DIR__,3).'/app/area_photo_storage.php';
require_once dirname(__DIR__,3).'/app/bootstrap.php';
require_once __DIR__.'/common.php';
try{
 $id=(int)($_GET['id']??0);$kind=$_GET['kind']??'';$bytes=null;
 if($kind==='areas'){$r=ptQuery('SELECT p.content,p.file_path FROM svm_area_photos p JOIN svm_areas a ON a.id=p.area_id AND a.project_id=p.project_id WHERE p.id=? AND p.project_id=?',[$id,$project])->get_result()->fetch_assoc();$bytes=$r?apsRead($r):null;}
 else{
  if($kind==='trees')$r=ptQuery("SELECT p.url FROM trees_photo p JOIN trees t ON t.id=p.tree_id WHERE p.id=? AND t.project_id=? AND p.status='active'",[$id,$project])->get_result()->fetch_assoc();
  elseif($kind==='main')$r=ptQuery('SELECT photo_url AS url FROM trees WHERE id=? AND project_id=?',[$id,$project])->get_result()->fetch_assoc();else throw new RuntimeException();
  $u=parse_url($r['url']??'');if(!$u||isset($u['host'])&&!in_array(strtolower($u['host']),[strtolower((string)parse_url(svmUrl(),PHP_URL_HOST))],true))throw new RuntimeException();$path=rawurldecode($u['path']??'');if(str_starts_with($path,'/map/public/'))$path=substr($path,12);$folder=explode('/',$path)[0];if(!in_array($folder,['uploads','photos'],true)||str_contains($path,"\0"))throw new RuntimeException();$base=realpath(__DIR__.'/../'.$folder);$file=realpath(__DIR__.'/../'.$path);if(!$base||!$file||!str_starts_with($file,$base.DIRECTORY_SEPARATOR)||!is_file($file)||filesize($file)>10*1024*1024)throw new RuntimeException();$bytes=file_get_contents($file);
 }
 if(!$bytes||strlen($bytes)>10*1024*1024)throw new RuntimeException();$info=getimagesizefromstring($bytes);if(!$info||!in_array($info['mime'],['image/jpeg','image/png','image/webp'],true))throw new RuntimeException();
 // Bound pixel count before decoding; keep output lightweight when GD is available.
 if($info[0]*$info[1]>24000000)throw new RuntimeException();
 if(function_exists('imagecreatefromstring')&&function_exists('imagescale')){$img=@imagecreatefromstring($bytes);if($img){$small=imagescale($img,min(1200,imagesx($img)));if($small){header('Content-Type: image/jpeg');imagejpeg($small,null,80);imagedestroy($small);imagedestroy($img);exit;}imagedestroy($img);}}
 header('Content-Type: '.$info['mime']);echo $bytes;
}catch(Throwable $e){http_response_code(404);header('Content-Type: text/plain; charset=utf-8');echo 'Fotografie není dostupná pro tisk.';}
