<?php
declare(strict_types=1);
require_once __DIR__.'/../../auth_api.php';
requireProjectRole(['owner','admin','editor']);
require_once __DIR__.'/api/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$path=null;$transaction=false;
function photoReplyError(string $message,int $status):never{http_response_code($status);echo json_encode(['success'=>false,'message'=>$message],JSON_UNESCAPED_UNICODE);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST')photoReplyError('Použijte POST.',405);
if(empty($_SESSION['tree_form_csrf'])||!hash_equals($_SESSION['tree_form_csrf'],(string)($_POST['tree_form_token']??''))||(int)($_POST['project_id']??0)!==(int)$activeProjectId)photoReplyError('Platnost formuláře nebo projekt se změnil. Obnovte formulář.',403);
$id=(int)($_POST['id']??0);$file=$_FILES['photo']??null;
if($id<1)photoReplyError('Nejdříve uložte strom.',422);
if(!$file||is_array($file['error'])||$file['error']!==UPLOAD_ERR_OK)photoReplyError('Soubor se nepodařilo přijmout. Zkontrolujte limit hostingu.',422);
if($file['size']>5*1024*1024||$file['size']<1)photoReplyError('Fotografie může mít nejvýše 5 MB.',422);
$info=@getimagesize($file['tmp_name']);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
if(!$info||!$ext||$info[0]*$info[1]>24000000)photoReplyError('Použijte JPG, PNG nebo WebP do 24 megapixelů.',422);
try{
 $mysqli->begin_transaction();$transaction=true;
 $q=$mysqli->prepare('SELECT * FROM trees WHERE id=? AND project_id=? FOR UPDATE');$q->bind_param('ii',$id,$activeProjectId);$q->execute();$tree=$q->get_result()->fetch_assoc();
 if(!$tree){$mysqli->rollback();photoReplyError('Strom nebyl nalezen v projektu.',404);}
 ksort($tree);$version=hash('sha256',json_encode($tree,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
 if(!hash_equals($version,(string)($_POST['row_version']??''))){$mysqli->rollback();photoReplyError('Strom mezitím změnil jiný uživatel. Fotografie nebyla nahrána. Nejprve otevřete aktuální záznam.',409);}
 $dir=__DIR__.'/uploads';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('directory');
 $url='uploads/'.bin2hex(random_bytes(20)).'.'.$ext;$path=__DIR__.'/'.$url;
 if(!move_uploaded_file($file['tmp_name'],$path))throw new RuntimeException('upload');
 $q=$mysqli->prepare("SELECT COALESCE(MAX(sort_order),-1)+1 AS next_sort FROM trees_photo WHERE tree_id=? AND status='active'");$q->bind_param('i',$id);$q->execute();$sort=(int)$q->get_result()->fetch_assoc()['next_sort'];$note='';
 $q=$mysqli->prepare('INSERT INTO trees_photo(tree_id,url,note_photo,sort_order) VALUES(?,?,?,?)');$q->bind_param('issi',$id,$url,$note,$sort);if(!$q->execute())throw new RuntimeException('insert');$photoId=$mysqli->insert_id;
 $q=$mysqli->prepare("UPDATE trees SET photo_url=(SELECT url FROM trees_photo WHERE tree_id=? AND status='active' ORDER BY sort_order,id LIMIT 1) WHERE id=? AND project_id=?");$q->bind_param('iii',$id,$id,$activeProjectId);if(!$q->execute())throw new RuntimeException('update');
 $q=$mysqli->prepare('SELECT * FROM trees WHERE id=? AND project_id=?');$q->bind_param('ii',$id,$activeProjectId);$q->execute();$updated=$q->get_result()->fetch_assoc();ksort($updated);
 $newVersion=hash('sha256',json_encode($updated,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
 $mysqli->commit();$transaction=false;
 echo json_encode(['success'=>true,'photo'=>['id'=>$photoId,'url'=>$url,'note_photo'=>'','in_history'=>0],'row_version'=>$newVersion],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(Throwable $e){if($transaction)$mysqli->rollback();if($path&&is_file($path))unlink($path);photoReplyError('Fotografii se nepodařilo uložit. Zkuste to znovu.',500);}
