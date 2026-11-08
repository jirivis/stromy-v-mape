<?php
require_once __DIR__.'/common.php';require_once __DIR__.'/import_core.php';
$tx=false;
try{
 ptCheckPost();requireProjectRole(['owner','admin','editor']);if(!$canEdit)throw new RuntimeException('Nemáte právo importovat.',403);
 $raw=file_get_contents('php://input',false,null,0,3000001);if(strlen($raw)>3000000)throw new InvalidArgumentException('Import je příliš velký.');$d=json_decode($raw,true,32,JSON_THROW_ON_ERROR);$action=$_GET['action']??'';
 $schema=[];foreach(ptQuery('SHOW COLUMNS FROM trees')->get_result()->fetch_all(MYSQLI_ASSOC) as $c)$schema[$c['Field']]=$c;
 $fields=imFields();
 if($action==='preview'){
  unset($_SESSION['svm_import_plan']);$mode=$d['mode']??'';if(!in_array($mode,['create','update'],true))throw new InvalidArgumentException('Zvolte přidání nebo aktualizaci.');$rows=$d['rows']??null;if(!is_array($rows)||!count($rows)||count($rows)>500)throw new InvalidArgumentException('Importujte 1 až 500 stromů.');
  $existing=[];foreach(ptRows('trees') as $r)$existing[(string)(int)$r['usr_id']][]=$r;
  $plan=[];$preview=[];$errors=[];$seen=[];
  foreach($rows as $i=>$row){try{if(!is_array($row))throw new InvalidArgumentException('Neplatný řádek.');$patch=imPatch($row,$schema,$fields);$number=(string)(int)$patch['usr_id'];if(isset($seen[$number]))throw new InvalidArgumentException('Číslo stromu se v souboru opakuje: '.$number);$seen[$number]=true;$hits=$existing[$number]??[];
   if($mode==='create'&&$hits)throw new InvalidArgumentException('Strom '.$number.' již v projektu existuje.');
   if($mode==='update'&&count($hits)!==1)throw new InvalidArgumentException('Číslo '.$number.' nenalezeno nebo není jednoznačné v aktivním projektu.');
   $old=$hits[0]??null;$values=$mode==='create'?imCreateValues($patch,$schema,$fields,$project):$patch;
   $changes=[];foreach($values as $k=>$v)if($k!=='project_id'&&($old===null||(string)($old[$k]??'')!==(string)($v??'')))$changes[$k]=['before'=>$old[$k]??null,'after'=>$v];
   $plan[]=['id'=>$old['id']??null,'number'=>$number,'version'=>$old?ptVersion($old):null,'values'=>$values,'changes'=>$changes];$preview[]=['number'=>$number,'changes'=>$changes];
  }catch(Throwable $e){$errors[]='Řádek '.($i+1).': '.$e->getMessage();}}
  $token=null;if(!$errors){$token=bin2hex(random_bytes(24));$_SESSION['svm_import_plan']=['token'=>$token,'expires'=>time()+1800,'project'=>$project,'user'=>(int)$_SESSION['user_id'],'mode'=>$mode,'rows'=>$plan];}
  ptOut(['preview'=>$preview,'errors'=>$errors,'token'=>$token,'project_id'=>$project]);
 }
 if($action==='commit'){
  $p=$_SESSION['svm_import_plan']??null;
  if(!$p||$p['expires']<time()||$p['project']!==$project||$p['user']!==(int)$_SESSION['user_id']||!is_string($d['token']??null)||!hash_equals($p['token'],$d['token']))throw new RuntimeException('Náhled vypršel nebo se změnil projekt. Připravte nový náhled.',409);
  $mysqli->begin_transaction();$tx=true;
  // Lock project and current rows for the duration of the all-or-nothing batch.
  ptQuery('SELECT id FROM projects WHERE id=? FOR UPDATE',[$project]);$current=[];foreach(ptRows('trees',true) as $r)$current[(string)(int)$r['usr_id']][]=$r;
  foreach($p['rows'] as $r){$hits=$current[$r['number']]??[];if($p['mode']==='create'&&$hits)throw new RuntimeException('Číslo '.$r['number'].' mezitím přibylo. Nic nebylo uloženo.',409);if($p['mode']==='update'&&(count($hits)!==1||(int)$hits[0]['id']!==(int)$r['id']||!hash_equals($r['version'],ptVersion($hits[0]))))throw new RuntimeException('Strom '.$r['number'].' mezitím někdo změnil. Nic nebylo uloženo.',409);}
  $count=0;foreach($p['rows'] as $r){$v=$r['values'];$keys=array_keys($v);foreach($keys as $key)if(!isset($schema[$key])||($key!=='project_id'&&!isset($fields[$key])))throw new InvalidArgumentException('Struktura databáze se změnila.');
   if($p['mode']==='create'){ptQuery('INSERT INTO trees (`'.implode('`,`',$keys).'`) VALUES ('.implode(',',array_fill(0,count($keys),'?')).')',array_values($v));$id=(int)$mysqli->insert_id;}
   else{if(!$r['changes'])continue;$id=$r['id'];ptQuery('UPDATE trees SET '.implode(',',array_map(fn($k)=>'`'.$k.'`=?',$keys)).' WHERE id=? AND project_id=?',[...array_values($v),$id,$project]);}
   ptQuery('INSERT INTO svm_table_audit(project_id,user_id,object_type,object_id,changes_json) VALUES(?,?,?,?,?)',[$project,(int)$_SESSION['user_id'],'trees',$id,ptJson(['source'=>'import','mode'=>$p['mode'],'fields'=>$r['changes']])]);$count++;
  }
  $mysqli->commit();$tx=false;unset($_SESSION['svm_import_plan']);ptOut(['ok'=>true,'count'=>$count,'project_id'=>$project]);
 }
 throw new InvalidArgumentException('Neznámá operace.');
}catch(Throwable $e){if($tx)$mysqli->rollback();$code=in_array($e->getCode(),[403,409],true)?$e->getCode():422;if($e instanceof mysqli_sql_exception){error_log('Import: '.$e->getMessage());$message='Databáze import nepřijala. Nic nebylo uloženo. Ověřte povinná pole a PHP error log.';}else $message=$e->getMessage();ptOut(['error'=>$message],$code);}
