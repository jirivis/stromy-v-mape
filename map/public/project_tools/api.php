<?php
if(in_array($_GET['action']??'', ['templates','save_template','delete_template'],true))define('PT_TEMPLATES_ONLY',true);
require_once __DIR__.'/common.php';
require_once __DIR__.'/../areas/history.php';
require_once dirname(__DIR__,3).'/app/public_map.php';
$transaction=false;
try{
 $action=$_GET['action']??'rows';
 if($_SERVER['REQUEST_METHOD']==='GET'){
  if($action==='templates'){if(!$canConfigure)throw new RuntimeException('Struktury může spravovat administrátor projektu.',403);$templates=ptQuery('SELECT id,name,fields_json,version FROM svm_form_templates WHERE owner_user_id=? ORDER BY name',[(int)$_SESSION['user_id']])->get_result()->fetch_all(MYSQLI_ASSOC);foreach($templates as &$t){$t['hidden']=json_decode($t['fields_json'],true);unset($t['fields_json']);}unset($t);ptOut(['project_id'=>$project,'templates'=>$templates,'catalog'=>ptCatalog()]);}
  if($action==='settings')ptOut(['project_id'=>$project,'catalog'=>ptCatalog(),'settings'=>ptSettings(),'can_configure'=>$canConfigure]);
  if($action==='filter_fields'){$settings=ptSettings();ptOut(['project_id'=>$project,'trees'=>ptTableFields('trees',$settings['hidden']['trees']??[]),'areas'=>ptTableFields('areas',$settings['hidden']['areas']??[])]);}
  if($action==='rows'){
   $kind=($_GET['kind']??'trees')==='areas'?'areas':'trees';$rows=ptRows($kind);$out=[];$public=[];if(pmReady($mysqli))foreach(pmQuery($mysqli,'SELECT object_id,enabled FROM svm_public_objects WHERE project_id=? AND kind=?',[$project,$kind])->get_result()->fetch_all(MYSQLI_ASSOC) as $pub)$public[(string)$pub['object_id']]=(bool)$pub['enabled'];
   foreach($rows as $r){$v=$r;if($kind==='areas'){$v=array_merge(json_decode($r['attributes_json'],true),['id'=>$r['id'],'code'=>$r['code'],'area_m2'=>$r['area_m2']]);}$out[]=['public'=>$public[(string)$r['id']]??false,'id'=>$r['id'],'version'=>ptVersion($r),'values'=>$v,'geometry'=>ptGeometry($kind,$r)];}
   ptOut(['project_id'=>$project,'rows'=>$out,'fields'=>$kind==='trees'?ptTreeFields():ptAreaFields(),'can_publish'=>$canConfigure&&pmReady($mysqli),'can_edit'=>$canEdit,'table_fields'=>ptTableFields($kind,ptSettings()['hidden'][$kind]??[])]);
  }
  throw new RuntimeException('Neznámá operace.',404);
 }
 ptCheckPost();$raw=file_get_contents('php://input',false,null,0,2000001);if(strlen($raw)>2000000)throw new RuntimeException('Příliš velký požadavek.',413);$data=json_decode($raw,true,64,JSON_THROW_ON_ERROR);
 if($action==='delete_rows'){require_once __DIR__.'/delete_rows.php';$count=ptDeleteRows($data);ptOut(['ok'=>true,'count'=>$count,'project_id'=>$project]);}
 if($action==='cards'){
  $selection=$data['selection']??null;if(!is_array($selection))throw new InvalidArgumentException('Chybí výběr.');$ids=[];$count=0;
  foreach(['trees','areas'] as $kind){$list=$selection[$kind]??[];if(!is_array($list)||count($list)>50)throw new InvalidArgumentException('Nejvýše 50 objektů.');$ids[$kind]=[];foreach($list as $id){if(!ctype_digit((string)$id)||(int)$id<1)throw new InvalidArgumentException('Neplatný výběr.');$ids[$kind][]=(int)$id;}$ids[$kind]=array_values(array_unique($ids[$kind]));$count+=count($ids[$kind]);}
  if($count<1||$count>50)throw new InvalidArgumentException('Vyberte 1 až 50 objektů.');$cards=[];$settings=ptSettings();
  foreach($ids as $kind=>$list){if(!$list)continue;$table=$kind==='trees'?'trees':'svm_areas';$rows=ptQuery('SELECT * FROM '.$table.' WHERE project_id=? AND id IN ('.implode(',',array_fill(0,count($list),'?')).') ORDER BY id',[$project,...$list])->get_result()->fetch_all(MYSQLI_ASSOC);if(count($rows)!==count($list))throw new RuntimeException('Některý objekt již není v aktivním projektu.',404);
   foreach($rows as $r){$values=$kind==='trees'?$r:array_merge(json_decode($r['attributes_json'],true),['code'=>$r['code'],'area_m2'=>$r['area_m2']]);$photos=[];
    if($kind==='areas'){$ps=ptQuery('SELECT id FROM svm_area_photos WHERE project_id=? AND area_id=? ORDER BY id LIMIT 2',[$project,$r['id']])->get_result()->fetch_all(MYSQLI_ASSOC);foreach($ps as $p)$photos[]='card_photo.php?kind=areas&id='.(int)$p['id'];}
    else{$ps=ptQuery("SELECT id FROM trees_photo WHERE tree_id=? AND status='active' ORDER BY sort_order,id LIMIT 2",[$r['id']])->get_result()->fetch_all(MYSQLI_ASSOC);foreach($ps as $p)$photos[]='card_photo.php?kind=trees&id='.(int)$p['id'];if(!$photos&&!empty($r['photo_url']))$photos[]='card_photo.php?kind=main&id='.(int)$r['id'];}
    $fields=ptTableFields($kind,$settings['hidden'][$kind]??[]);$safe=[];foreach($fields as $key=>$f)$safe[$key]=$values[$key]??null;
    $cards[]=['id'=>(int)$r['id'],'kind'=>$kind,'code'=>$values[$kind==='trees'?'usr_id':'code']??'bez čísla','values'=>$safe,'fields'=>$fields,'geometry'=>ptGeometry($kind,$r),'photos'=>$photos];
   }
  }ptOut(['project_id'=>$project,'cards'=>$cards]);
 }
 if($action==='save_template'||$action==='delete_template'){
  if(!$canConfigure)throw new RuntimeException('Struktury může spravovat administrátor projektu.',403);
  $id=(int)($data['id']??0);$owner=(int)$_SESSION['user_id'];
  $mysqli->begin_transaction();$transaction=true;
  if($id>0){$old=ptQuery('SELECT * FROM svm_form_templates WHERE id=? AND owner_user_id=? FOR UPDATE',[$id,$owner])->get_result()->fetch_assoc();if(!$old)throw new RuntimeException('Struktura nebyla nalezena.',404);if((int)($data['version']??0)!==(int)$old['version'])throw new RuntimeException('Struktura se změnila. Obnovte stránku.',409);}
  if($action==='delete_template'){if(!$id)throw new InvalidArgumentException('Vyberte strukturu.');ptQuery('DELETE FROM svm_form_templates WHERE id=? AND owner_user_id=?',[$id,$owner]);}
  else{$name=trim((string)($data['name']??''));if($name===''||mb_strlen($name)>120)throw new InvalidArgumentException('Název musí mít 1 až 120 znaků.');$hidden=ptHidden($data['hidden']??null);if(ptQuery('SELECT id FROM svm_form_templates WHERE owner_user_id=? AND name=? AND id<>?',[$owner,$name,$id])->get_result()->fetch_assoc())throw new InvalidArgumentException('Struktura s tímto názvem již existuje. Vyberte ji pro úpravu nebo zvolte jiný název.');if($id)ptQuery('UPDATE svm_form_templates SET name=?,fields_json=?,version=version+1 WHERE id=? AND owner_user_id=?',[$name,ptJson($hidden),$id,$owner]);else{ptQuery('INSERT INTO svm_form_templates(owner_user_id,name,fields_json) VALUES(?,?,?)',[$owner,$name,ptJson($hidden)]);$id=(int)$mysqli->insert_id;}}
  $mysqli->commit();$transaction=false;ptOut(['ok'=>true,'id'=>$id]);
 }
 if($action==='settings'){
  if(!$canConfigure)throw new RuntimeException('Nastavení může měnit jen vlastník nebo administrátor projektu.',403);
  $hidden=[];foreach(ptCatalog() as $kind=>$fields){$list=$data['hidden'][$kind]??null;if(!is_array($list)||count($list)>100)throw new InvalidArgumentException('Neplatná pole.');foreach($list as $k)if(!is_string($k)||!isset($fields[$k]))throw new InvalidArgumentException('Neznámé pole.');$hidden[$kind]=array_values(array_unique($list));}
  $profiles=$data['profiles']??[];if(!is_array($profiles)||count($profiles)>20)throw new InvalidArgumentException('Nejvýše 20 sestav.');
  foreach($profiles as $name=>$profile){if(!is_string($name)||strlen($name)>120||trim($name)==='')throw new InvalidArgumentException('Neplatný název sestavy.');foreach(ptCatalog() as $kind=>$known){if(!isset($profile[$kind])||!is_array($profile[$kind])||count($profile[$kind])>100)throw new InvalidArgumentException('Neplatná sestava.');foreach($profile[$kind] as $key)if(!is_string($key)||!isset($known[$key]))throw new InvalidArgumentException('Neznámé pole sestavy.');}}
  $templateName=trim((string)($data['template_name']??''));if(mb_strlen($templateName)>120)throw new InvalidArgumentException('Příliš dlouhý název struktury.');$storedSettings=['hidden'=>$hidden,'profiles'=>$profiles,'template_name'=>$templateName,'after_save'=>svmFormBehavior($data['after_save']??ptSettings()['after_save'],true)];
  $mysqli->begin_transaction();$transaction=true;$old=ptQuery('SELECT version FROM svm_form_settings WHERE project_id=? FOR UPDATE',[$project])->get_result()->fetch_assoc();
  if((int)($data['version']??-1)!==(int)($old['version']??0))throw new RuntimeException('Nastavení mezitím změnil jiný správce. Obnovte stránku.',409);
  if($old)ptQuery('UPDATE svm_form_settings SET settings_json=?,version=version+1 WHERE project_id=?',[ptJson($storedSettings),$project]);else ptQuery('INSERT INTO svm_form_settings(project_id,settings_json) VALUES(?,?)',[$project,ptJson($storedSettings)]);
  $mysqli->commit();$transaction=false;ptOut(['settings'=>ptSettings()]);
 }
 if($action==='save_rows'){
  if(!$canEdit)throw new RuntimeException('Nemáte oprávnění k úpravám.',403);
  $kind=$data['kind']??'';if(!in_array($kind,['trees','areas'],true))throw new InvalidArgumentException('Neplatný typ.');$changes=$data['rows']??[];
  if(!is_array($changes)||count($changes)<1||count($changes)>500)throw new InvalidArgumentException('Ukládejte 1 až 500 řádků najednou.');
  $fields=$kind==='trees'?ptTreeFields():ptAreaFields();$mysqli->begin_transaction();$transaction=true;
  $table=$kind==='trees'?'trees':'svm_areas';$schema=[];if($kind==='trees'){foreach(ptQuery('SHOW COLUMNS FROM trees')->get_result()->fetch_all(MYSQLI_ASSOC) as $column)$schema[$column['Field']]=$column;} $seen=[];
  foreach($changes as $change){$id=(int)($change['id']??0);if(isset($seen[$id]))throw new InvalidArgumentException('Duplicitní řádek.');$seen[$id]=true;
   $old=ptQuery("SELECT * FROM $table WHERE id=? AND project_id=? FOR UPDATE",[$id,$project])->get_result()->fetch_assoc();if(!$old)throw new RuntimeException('Objekt již není v aktivním projektu.',404);
   if(!hash_equals(ptVersion($old),(string)($change['version']??'')))throw new RuntimeException('Objekt '.($old['usr_id']??$old['code']??'bez čísla').' mezitím někdo změnil. Nic nebylo uloženo; obnovte tabulku.',409);
   $patch=$change['patch']??[];if(!is_array($patch)||!count($patch))throw new InvalidArgumentException('Chybí změny.');$values=[];
   foreach($patch as $k=>$v){if(!isset($fields[$k])||($kind==='trees'&&!array_key_exists($k,$old)))throw new InvalidArgumentException('Pole nelze upravit: '.$k);if(!is_string($v)||strlen($v)>5000)throw new InvalidArgumentException('Neplatná hodnota.');$f=$fields[$k];
    if($f['type']==='number'){if($v==='')$v=null;else{$v=str_replace(',','.',$v);if(!is_numeric($v)||!is_finite((float)$v))throw new InvalidArgumentException('Neplatné číslo: '.$k);$v=(float)$v;if(!in_array($k,['lat_wgs84','lng_wgs84','x_sjtsk','y_sjtsk'])&&$v<0)throw new InvalidArgumentException('Záporná hodnota: '.$k);}}
    if($f['type']==='date'){$v=areaDate($v);if($kind==='trees'&&$v==='')$v=null;}
    if($f['type']==='select'&&count($f['options'])&&!array_key_exists($v,$f['options']))throw new InvalidArgumentException('Neplatná volba: '.$k);
    if($kind==='trees'&&in_array($k,['phy_age','landscape_value'])&&$v==='')$v=null;
    if($kind==='trees'&&in_array($k,['age','usr_id'])&&$v!==null&&floor((float)$v)!=(float)$v)throw new InvalidArgumentException('Zadejte celé číslo: '.$k);
    if($kind==='trees'&&isset($schema[$k])){if(preg_match('/^(?:var)?char\((\d+)\)/i',$schema[$k]['Type'],$m)&&mb_strlen((string)$v)>(int)$m[1])throw new InvalidArgumentException('Text je příliš dlouhý: '.$k);}
    $values[$k]=$v;
   }
   if($kind==='areas'){areaHistoryBaseline($mysqli,$old);$a=areaAttributes(array_replace(json_decode($old['attributes_json'],true),$values));ptQuery('UPDATE svm_areas SET attributes_json=?,version=version+1 WHERE id=? AND project_id=?',[ptJson($a),$id,$project]);$saved=ptQuery('SELECT * FROM svm_areas WHERE id=? AND project_id=?',[$id,$project])->get_result()->fetch_assoc();areaHistoryRecord($mysqli,$saved,'table');}
   else{
    if(array_key_exists('usr_id',$values)){if($values['usr_id']===null||$values['usr_id']<1||floor($values['usr_id'])!=$values['usr_id'])throw new InvalidArgumentException('Číslo stromu musí být kladné celé číslo.');if(ptQuery('SELECT id FROM trees WHERE project_id=? AND usr_id=? AND id<>? FOR UPDATE',[$project,$values['usr_id'],$id])->get_result()->fetch_assoc())throw new InvalidArgumentException('Číslo stromu je již použité.');}
    foreach(['lat_wgs84'=>90,'lng_wgs84'=>180] as $k=>$limit)if(isset($values[$k])&&abs($values[$k])>$limit)throw new InvalidArgumentException('Souřadnice mimo rozsah.');
    $cols=array_map(fn($k)=>'`'.$k.'`=?',array_keys($values));ptQuery('UPDATE trees SET '.implode(',',$cols).' WHERE id=? AND project_id=?',[...array_values($values),$id,$project]);
   }
   $before=$kind==='areas'?json_decode($old['attributes_json'],true):$old;$audit=[];foreach($values as $k=>$v)$audit[$k]=['before'=>$before[$k]??null,'after'=>$v];ptQuery('INSERT INTO svm_table_audit(project_id,user_id,object_type,object_id,changes_json) VALUES(?,?,?,?,?)',[$project,(int)$_SESSION['user_id'],$kind,$id,ptJson($audit)]);
  }
  $mysqli->commit();$transaction=false;ptOut(['ok'=>true,'count'=>count($changes)]);
 }
 throw new RuntimeException('Neznámá operace.',404);
}catch(Throwable $e){if($transaction)$mysqli->rollback();$code=$e instanceof RuntimeException&&in_array($e->getCode(),[403,404,405,409,413])?$e->getCode():($e instanceof InvalidArgumentException||$e instanceof JsonException?422:500);if($code===500)error_log('Project tools: '.$e->getMessage());ptOut(['error'=>$code===500?'Operace selhala. Ověřte instalaci SQL a záznam v PHP error logu.':$e->getMessage()],$code);}
