<?php
require_once __DIR__.'/common.php';require_once __DIR__.'/import_core.php';require_once __DIR__.'/import_area_core.php';require_once __DIR__.'/../areas/history.php';$tx=false;
try{
 ptCheckPost();requireProjectRole(['owner','admin','editor']);if(!$canEdit)throw new RuntimeException('Nemáte právo importovat.',403);
 $raw=file_get_contents('php://input',false,null,0,3000001);if(strlen($raw)>3000000)throw new InvalidArgumentException('Import je příliš velký.');$d=json_decode($raw,true,64,JSON_THROW_ON_ERROR);$action=$_GET['action']??'';
 if($action==='preview'){
  unset($_SESSION['svm_area_import_plan']);$mode=$d['mode']??'';$rows=$d['rows']??null;if(!in_array($mode,['create','update'],true)||!is_array($rows)||!count($rows)||count($rows)>50)throw new InvalidArgumentException('Zvolte operaci a 1 až 50 ploch.');
  $existing=[];foreach(ptRows('areas') as $r)$existing[mb_strtolower($r['code'],'UTF-8')][]=$r;$seen=[];$plan=[];$preview=[];$errors=[];
  foreach($rows as $i=>$row){try{if(!is_array($row))throw new InvalidArgumentException('Neplatný řádek.');$code=iaCode($row['code']??'');$key=mb_strtolower($code,'UTF-8');if(isset($seen[$key]))throw new InvalidArgumentException('Číslo plochy se opakuje: '.$code);$seen[$key]=true;$hits=$existing[$key]??[];
   if($mode==='create'&&$hits)throw new InvalidArgumentException('Plocha '.$code.' již existuje.');if($mode==='update'&&count($hits)!==1)throw new InvalidArgumentException('Plocha '.$code.' nebyla jednoznačně nalezena.');$old=$hits[0]??null;$v=iaValues($row,$old);$changes=[];$before=$old?json_decode($old['attributes_json'],true):[];$after=json_decode($v['attributes_json'],true);
   foreach(['code'=>$v['code'],'area_m2'=>$v['area_m2']]+$after as $k=>$value){$prev=in_array($k,['code','area_m2'],true)?($old[$k]??null):($before[$k]??null);if(ptJson($prev)!==ptJson($value))$changes[$k]=['before'=>$prev,'after'=>$value];}
   if(!$old||$old['geometry_json']!==$v['geometry_json'])$changes['geometry']=['before'=>$old?'Původní hranice':null,'after'=>'Hranice z GPKG (viz mapa)'];
   $plan[]=['id'=>$old['id']??null,'key'=>$key,'code'=>$code,'version'=>$old?ptVersion($old):null,'values'=>$v,'changes'=>$changes];$preview[]=['number'=>$code,'changes'=>$changes];
  }catch(Throwable $e){$errors[]='Objekt '.($i+1).': '.$e->getMessage();}}
  $token=null;if(!$errors){$token=bin2hex(random_bytes(24));$_SESSION['svm_area_import_plan']=['token'=>$token,'expires'=>time()+1800,'project'=>$project,'user'=>(int)$_SESSION['user_id'],'mode'=>$mode,'rows'=>$plan];}ptOut(['preview'=>$preview,'errors'=>$errors,'token'=>$token,'project_id'=>$project]);
 }
 if($action==='commit'){
  $p=$_SESSION['svm_area_import_plan']??null;if(!$p||$p['expires']<time()||$p['project']!==$project||$p['user']!==(int)$_SESSION['user_id']||!is_string($d['token']??null)||!hash_equals($p['token'],$d['token']))throw new RuntimeException('Náhled vypršel nebo se změnil projekt. Připravte nový náhled.',409);
  $mysqli->begin_transaction();$tx=true;ptQuery('SELECT id FROM projects WHERE id=? FOR UPDATE',[$project]);$current=[];foreach(ptRows('areas',true) as $r)$current[mb_strtolower($r['code'],'UTF-8')][]=$r;
  foreach($p['rows'] as $r){$hits=$current[$r['key']]??[];if($p['mode']==='create'&&$hits)throw new RuntimeException('Číslo '.$r['code'].' mezitím přibylo. Nic nebylo uloženo.',409);if($p['mode']==='update'&&(count($hits)!==1||(int)$hits[0]['id']!==(int)$r['id']||!hash_equals($r['version'],ptVersion($hits[0]))))throw new RuntimeException('Plochu '.$r['code'].' mezitím někdo změnil. Nic nebylo uloženo.',409);}
  $count=0;foreach($p['rows'] as $r){$v=$r['values'];if($p['mode']==='create'){ptQuery('INSERT INTO svm_areas(project_id,code,geometry_json,attributes_json,area_m2) VALUES(?,?,?,?,?)',[$project,$v['code'],$v['geometry_json'],$v['attributes_json'],$v['area_m2']]);$id=(int)$mysqli->insert_id;}
   else{if(!$r['changes'])continue;$id=(int)$r['id'];areaHistoryBaseline($mysqli,$current[$r['key']][0]);ptQuery('UPDATE svm_areas SET code=?,geometry_json=?,attributes_json=?,area_m2=?,version=version+1 WHERE id=? AND project_id=?',[$v['code'],$v['geometry_json'],$v['attributes_json'],$v['area_m2'],$id,$project]);}
   $saved=ptQuery('SELECT * FROM svm_areas WHERE id=? AND project_id=?',[$id,$project])->get_result()->fetch_assoc();areaHistoryRecord($mysqli,$saved,$p['mode']==='create'?'created':'updated');ptQuery('INSERT INTO svm_table_audit(project_id,user_id,object_type,object_id,changes_json) VALUES(?,?,?,?,?)',[$project,(int)$_SESSION['user_id'],'areas',$id,ptJson(['source'=>'import_gpkg','mode'=>$p['mode'],'fields'=>$r['changes']])]);$count++;
  }$mysqli->commit();$tx=false;unset($_SESSION['svm_area_import_plan']);ptOut(['ok'=>true,'count'=>$count,'project_id'=>$project]);
 }throw new InvalidArgumentException('Neznámá operace.');
}catch(Throwable $e){if($tx)$mysqli->rollback();if($e instanceof mysqli_sql_exception){error_log('GPKG import areas: '.$e->getMessage());$message='Databáze import nepřijala. Nic nebylo uloženo.';}else $message=$e->getMessage();ptOut(['error'=>$message],in_array($e->getCode(),[403,409],true)?$e->getCode():422);}
