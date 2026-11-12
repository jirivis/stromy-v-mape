<?php
declare(strict_types=1);
require_once __DIR__.'/common.php';require_once dirname(__DIR__,3).'/app/area_photo_storage.php';require_once __DIR__.'/photo_export_helpers.php';
$tx=false;$newPath=null;
try{
 ptCheckPost();requireProjectRole(['owner','admin','editor']);if(!$canEdit)throw new RuntimeException('Nemáte právo importovat.',403);
 $action=$_GET['action']??'';
 if($action==='preview'){
  $raw=file_get_contents('php://input',false,null,0,2000001);if(strlen($raw)>2000000)throw new InvalidArgumentException('Příliš velká dávka.');$d=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
  $kind=$d['kind']??'';if(!in_array($kind,['trees','areas'],true))throw new InvalidArgumentException('Neplatný typ objektů.');
  $rows=$d['rows']??null;if(!is_array($rows)||!array_is_list($rows)||!count($rows)||count($rows)>500)throw new InvalidArgumentException('Vyberte 1 až 500 fotografií.');
  $table=$kind==='trees'?'trees':'svm_areas';$column=$kind==='trees'?'usr_id':'code';$items=[];$seen=[];
  foreach($rows as $r){
   if(!is_array($r))throw new InvalidArgumentException('Neplatný řádek.');foreach(['file','code','note','sha256'] as $k)if(!isset($r[$k])||!is_string($r[$k]))throw new InvalidArgumentException('Chybí textové hodnoty přiřazení.');
   if(strlen($r['file'])>1000||str_contains($r['file'],"\0")||mb_strlen($r['code'])>100||mb_strlen($r['note'])>1000||!preg_match('/^[a-f0-9]{64}$/D',$r['sha256']))throw new InvalidArgumentException('Neplatné přiřazení fotografie.');
   if(isset($seen[$r['file']]))throw new InvalidArgumentException('Fotografie je v tabulce vícekrát: '.$r['file']);$seen[$r['file']]=true;
   $code=trim($r['code']);if($code===''||($kind==='trees'&&!preg_match('/^[0-9]{1,10}$/D',$code)))throw new InvalidArgumentException('Neplatné číslo objektu: '.$code);
   $found=ptQuery('SELECT id FROM '.$table.' WHERE project_id=? AND '.$column.'=?',[$project,$code])->get_result()->fetch_all(MYSQLI_ASSOC);
   if(count($found)!==1)throw new InvalidArgumentException('Objekt č. '.$code.' nebyl nalezen jednoznačně.');
   $items[]=['id'=>(int)$found[0]['id'],'file'=>$r['file'],'code'=>$code,'note'=>$r['note'],'sha256'=>$r['sha256'],'done'=>false];
  }
  $token=bin2hex(random_bytes(24));$_SESSION['photo_import_plan']=['token'=>$token,'project'=>$project,'kind'=>$kind,'expires'=>time()+3600,'items'=>$items];ptOut(['token'=>$token,'count'=>count($items),'project_id'=>$project]);
 }
 if($action!=='upload')throw new InvalidArgumentException('Neznámá operace.');
 $plan=&$_SESSION['photo_import_plan'];if(!is_array($plan)||!hash_equals($plan['token'],(string)($_POST['plan']??''))||$plan['project']!==$project||$plan['expires']<time())throw new RuntimeException('Kontrolní náhled vypršel nebo se změnil projekt. Připravte nový.',409);
 $index=filter_var($_POST['index']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);if($index===false||!isset($plan['items'][$index]))throw new InvalidArgumentException('Neplatná fotografie.');$item=&$plan['items'][$index];
 if($item['done'])ptOut(['ok'=>true,'already_imported'=>true,'project_id'=>$project]);
 $f=$_FILES['photo']??null;if(!$f||is_array($f['error'])||$f['error']!==UPLOAD_ERR_OK||!is_uploaded_file($f['tmp_name'])||$f['size']<1||$f['size']>5*1024*1024)throw new InvalidArgumentException('Fotografie se nenahrála. Limit je 5 MB.');
 if(!hash_equals($item['sha256'],hash_file('sha256',$f['tmp_name'])))throw new InvalidArgumentException('Soubor neodpovídá kontrolnímu náhledu.');
 $info=@getimagesize($f['tmp_name']);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
 if(!$info||!$ext||$info[0]*$info[1]>24000000)throw new InvalidArgumentException('Použijte JPG, PNG nebo WebP do 24 megapixelů.');
 $tree=$plan['kind']==='trees';$table=$tree?'trees':'svm_areas';$column=$tree?'usr_id':'code';
 $mysqli->begin_transaction();$tx=true;$record=ptQuery('SELECT * FROM '.$table.' WHERE id=? AND project_id=? AND '.$column.'=? FOR UPDATE',[$item['id'],$project,$item['code']])->get_result()->fetch_assoc();
 if(!$record)throw new RuntimeException('Objekt byl změněn nebo odstraněn. Připravte nový náhled.',409);
 if($tree){
  $dir=__DIR__.'/../uploads';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Nelze vytvořit složku fotografií.');$stamp=exCaptureStamp($f['tmp_name'],$f['name']);$url='uploads/'.($stamp?$stamp.'_':'').bin2hex(random_bytes(20)).'.'.$ext;$newPath=__DIR__.'/../'.$url;
  if(!move_uploaded_file($f['tmp_name'],$newPath))throw new RuntimeException('Fotografii nelze uložit.');
  $sort=(int)ptQuery("SELECT COALESCE(MAX(sort_order),-1)+1 AS n FROM trees_photo WHERE tree_id=? AND status='active'",[$item['id']])->get_result()->fetch_assoc()['n'];
  ptQuery('INSERT INTO trees_photo(tree_id,url,note_photo,sort_order) VALUES(?,?,?,?)',[$item['id'],$url,$item['note'],$sort]);
  if(empty($record['photo_url']))ptQuery('UPDATE trees SET photo_url=? WHERE id=? AND project_id=?',[$url,$item['id'],$project]);
 }else{
  $key=apsPut(file_get_contents($f['tmp_name']),$mime);$name=mb_substr(basename($f['name']),0,180);
  ptQuery('INSERT INTO svm_area_photos(area_id,project_id,original_name,mime,file_path,note) VALUES(?,?,?,?,?,?)',[$item['id'],$project,$name,$mime,$key,$item['note']]);
 }
 $mysqli->commit();$tx=false;$item['done']=true;$newPath=null;ptOut(['ok'=>true,'project_id'=>$project]);
}catch(Throwable $e){if($tx)$mysqli->rollback();if($newPath&&is_file($newPath))@unlink($newPath);$code=in_array($e->getCode(),[403,409],true)?$e->getCode():422;error_log('Photo import: '.$e->getMessage());ptOut(['error'=>$e instanceof mysqli_sql_exception?'Fotografii nelze zapsat do databáze.':$e->getMessage()],$code);}
