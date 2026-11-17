<?php
declare(strict_types=1);
require_once __DIR__.'/common.php';require_once dirname(__DIR__,3).'/app/public_map.php';$tx=false;
try{
 if(!$canConfigure)throw new RuntimeException('Zveřejnění a podněty spravuje vlastník nebo administrátor projektu.',403);
 if(!pmReady($mysqli))throw new RuntimeException('Nejprve dokončete databázovou přípravu veřejné mapy.',503);
 $action=$_GET['action']??'settings';
 if($_SERVER['REQUEST_METHOD']==='GET'){
  if($action==='settings')ptOut(['settings'=>pmSettings($mysqli,$project),'catalog'=>pmCatalog(),'project_id'=>$project]);
  if($action==='reports'){$page=max(0,min(100000,(int)($_GET['page']??0)));$rows=pmQuery($mysqli,'SELECT id,kind,object_code,message,contact,status,created_at FROM svm_public_reports WHERE project_id=? ORDER BY id DESC LIMIT 100 OFFSET '.($page*100),[$project])->get_result()->fetch_all(MYSQLI_ASSOC);ptOut(['rows'=>$rows,'page'=>$page,'project_id'=>$project]);}
  throw new RuntimeException('Neznámá operace.',404);
 }
 ptCheckPost();$raw=file_get_contents('php://input',false,null,0,100001);if(strlen($raw)>100000)throw new InvalidArgumentException('Příliš velký požadavek.');$d=json_decode($raw,true,32,JSON_THROW_ON_ERROR);$mysqli->begin_transaction();$tx=true;
 if($action==='settings'){
  $fields=pmFields($d['fields']??null);foreach(['enabled','photos','photo_notes','reports','email_notify'] as $k)if(!is_bool($d[$k]??null))throw new InvalidArgumentException('Neplatná volba.');
  $old=pmQuery($mysqli,'SELECT version FROM svm_public_maps WHERE project_id=? FOR UPDATE',[$project])->get_result()->fetch_assoc();if((int)($d['version']??-1)!==(int)($old['version']??0))throw new RuntimeException('Nastavení změnil jiný správce. Obnovte stránku.',409);
  $args=[(int)$d['enabled'],json_encode($fields,JSON_THROW_ON_ERROR),(int)$d['photos'],(int)$d['photo_notes'],(int)$d['reports'],(int)$d['email_notify']];
  if($old)pmQuery($mysqli,'UPDATE svm_public_maps SET enabled=?,fields_json=?,photos=?,photo_notes=?,reports=?,email_notify=?,version=version+1 WHERE project_id=?',[...$args,$project]);else pmQuery($mysqli,'INSERT INTO svm_public_maps(enabled,fields_json,photos,photo_notes,reports,email_notify,project_id,token) VALUES(?,?,?,?,?,?,?,?)',[...$args,$project,bin2hex(random_bytes(24))]);
 }elseif($action==='publish'){
  $kind=$d['kind']??'';$ids=$d['ids']??null;if(!in_array($kind,['trees','areas'],true)||!is_bool($d['enabled']??null)||!is_array($ids)||!array_is_list($ids)||count($ids)<1||count($ids)>500)throw new InvalidArgumentException('Vyberte 1 až 500 objektů.');
  foreach($ids as $id)if((!is_int($id)&&!is_string($id))||!preg_match('/^[1-9][0-9]{0,9}$/D',(string)$id))throw new InvalidArgumentException('Neplatný výběr.');$ids=array_unique($ids);$table=$kind==='trees'?'trees':'svm_areas';
  foreach($ids as $id){if(!pmQuery($mysqli,'SELECT id FROM '.$table.' WHERE project_id=? AND id=? FOR UPDATE',[$project,$id])->get_result()->fetch_assoc())throw new RuntimeException('Objekt již není v tomto projektu.',409);pmQuery($mysqli,'INSERT INTO svm_public_objects(project_id,kind,object_id,token,enabled) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled)',[$project,$kind,$id,bin2hex(random_bytes(24)),(int)$d['enabled']]);}
 }elseif($action==='report_status'||$action==='delete_report'){
  $id=filter_var($d['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id||!pmQuery($mysqli,'SELECT id FROM svm_public_reports WHERE project_id=? AND id=? FOR UPDATE',[$project,$id])->get_result()->fetch_assoc())throw new RuntimeException('Podnět nebyl nalezen.',404);
  if($action==='delete_report')pmQuery($mysqli,'DELETE FROM svm_public_reports WHERE project_id=? AND id=?',[$project,$id]);else{if(!in_array($d['status']??'', ['new','working','done'],true))throw new InvalidArgumentException('Neplatný stav.');pmQuery($mysqli,'UPDATE svm_public_reports SET status=?,updated_at=NOW(),updated_by=? WHERE project_id=? AND id=?',[$d['status'],(int)$_SESSION['user_id'],$project,$id]);}
 }else throw new RuntimeException('Neznámá operace.',404);
 $mysqli->commit();$tx=false;ptOut(['ok'=>true,'project_id'=>$project]);
}catch(Throwable $e){if($tx)$mysqli->rollback();$code=in_array($e->getCode(),[403,404,409,503],true)?$e->getCode():422;ptOut(['error'=>$e instanceof mysqli_sql_exception?'Databázová operace selhala. Zkontrolujte přípravu aktualizace.':$e->getMessage()],$code);}
