<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_once dirname(__DIR__).'/map/public/project_tools/structure_catalog.php';
function pmQuery(mysqli $db,string $sql,array $args=[]):mysqli_stmt{$s=$db->prepare($sql);if($args)$s->bind_param(str_repeat('s',count($args)),...$args);$s->execute();return $s;}
function pmReady(mysqli $db):bool{static $ready;return $ready??=$db->query("SHOW TABLES LIKE 'svm_public_maps'")->num_rows===1;}
function pmCatalog():array{
 // Explicit, reviewed allowlist. Newly added database/form fields are never published automatically.
 $tree=ptTreeFields();$keys=['usr_id','species','czech','scientific_author','angio_gymno','cultivated_variety','dbh1_cm','dbh2_cm','dbh3_cm','dbh4_cm','girth1_cm','girth2_cm','girth3_cm','girth4_cm','height','canopy_h_m','spread_m','phy_age','age','vitality','tree_health','stability','operational_safety','longevity','ecological_value','landscape_value','planted','measured_at','last_insp_at','next_insp_at','note_public','death_date','removal_date'];$t=[];foreach($keys as $k)if(isset($tree[$k]))$t[$k]=$tree[$k];
 $t['removal_type']=['label'=>'Stav jedince','type'=>'select','options'=>['dead'=>'Odumřel','felled'=>'Pokácen','fallen'=>'Vyvrácen / zlomen','missing'=>'Nenalezen','other'=>'Jiný důvod']];
 $area=ptAreaFields();$a=['code'=>['label'=>'Číslo plochy'],'area_m2'=>['label'=>'Výměra (m²)'],'species'=>['label'=>'Druhové složení']];foreach(['name','height_min','height_max','planted','measured_at','last_insp_at','next_insp_at','suitability','management','biology','attractiveness','urgency','status'] as $k)$a[$k]=$area[$k];return ['trees'=>$t,'areas'=>$a];
}
function pmFields(mixed $fields):array{if(!is_array($fields))throw new InvalidArgumentException('Vyberte zveřejněná pole.');$out=[];foreach(pmCatalog() as $kind=>$catalog){$list=$fields[$kind]??null;if(!is_array($list)||count($list)>80)throw new InvalidArgumentException('Neplatný výběr polí.');foreach($list as $k)if(!is_string($k)||!isset($catalog[$k]))throw new InvalidArgumentException('Toto pole nelze zveřejnit.');$out[$kind]=array_values(array_unique($list));}return $out;}
function pmSettings(mysqli $db,int $project):array{$s=pmQuery($db,'SELECT * FROM svm_public_maps WHERE project_id=?',[$project])->get_result()->fetch_assoc();if(!$s)return ['project_id'=>$project,'enabled'=>0,'token'=>'','fields'=>['trees'=>[],'areas'=>[]],'photos'=>0,'photo_notes'=>0,'reports'=>0,'email_notify'=>0,'version'=>0];$s['fields']=pmFields(json_decode($s['fields_json'],true));unset($s['fields_json'],$s['last_mail_at']);return $s;}
function pmProject(mysqli $db,string $token):array{
 if(!preg_match('/^[a-f0-9]{48}$/D',$token)||!pmReady($db))throw new RuntimeException('Veřejná mapa není dostupná.',404);
 $r=pmQuery($db,'SELECT m.*,p.name FROM svm_public_maps m JOIN projects p ON p.id=m.project_id WHERE m.token=? AND m.enabled=1',[$token])->get_result()->fetch_assoc();if(!$r)throw new RuntimeException('Veřejná mapa není dostupná.',404);$r['fields']=pmFields(json_decode($r['fields_json'],true));return $r;
}
function pmObject(mysqli $db,array $project,string $token):array{
 if(!preg_match('/^[a-f0-9]{48}$/D',$token))throw new RuntimeException('Objekt není dostupný.',404);
 $o=pmQuery($db,'SELECT * FROM svm_public_objects WHERE project_id=? AND token=? AND enabled=1',[$project['project_id'],$token])->get_result()->fetch_assoc();if(!$o||!in_array($o['kind'],['trees','areas'],true))throw new RuntimeException('Objekt není dostupný.',404);
 $table=$o['kind']==='trees'?'trees':'svm_areas';$r=pmQuery($db,'SELECT * FROM '.$table.' WHERE project_id=? AND id=?',[$project['project_id'],$o['object_id']])->get_result()->fetch_assoc();if(!$r)throw new RuntimeException('Objekt není dostupný.',404);return [$o,$r];
}
function pmValues(string $kind,array $r,array $fields):array{
 $values=$kind==='trees'?$r:array_merge(json_decode($r['attributes_json'],true)?:[],['code'=>$r['code'],'area_m2'=>$r['area_m2']]);$out=[];$catalog=pmCatalog()[$kind];
 foreach($fields as $k){$v=$values[$k]??null;if($v===null||$v==='')continue;
  if($k==='species'&&$kind==='areas'){$v=implode('; ',array_map(static fn($s)=>trim((string)($s['scientific_name']??'').' '.(string)($s['czech_name']??'')),is_array($v)?$v:[]));}
  if(!is_scalar($v))continue;$out[]=['label'=>$catalog[$k]['label'],'value'=>(string)($catalog[$k]['options'][(string)$v]??$v)];
 }return $out;
}
// Only selected, allowlisted public values enter filtering; never expose whole database rows.
function pmFilterFields(string $kind,array $selected):array{
 $catalog=pmCatalog()[$kind];$out=[];
 foreach(array_unique(array_merge([$kind==='trees'?'usr_id':'code'],$selected)) as $key){
  if(!isset($catalog[$key]))continue;
  $out[$key]=array_intersect_key($catalog[$key],array_flip(['label','type','options']));
 }
 return $out;
}
function pmFilterValues(string $kind,array $row,array $selected):array{
 $values=$kind==='trees'?$row:array_merge(json_decode($row['attributes_json'],true)?:[],['code'=>$row['code'],'area_m2'=>$row['area_m2']]);
 $out=[];
 foreach(pmFilterFields($kind,$selected) as $key=>$meta){
  $value=$values[$key]??null;
  if($kind==='areas'&&$key==='species'){
   $display=pmValues($kind,$row,[$key]);$value=$display[0]['value']??'';
  }
  $out[$key]=is_scalar($value)?$value:null;
 }
 return $out;
}
function pmGeometry(string $kind,array $r):?array{
 if($kind==='trees'){$lat=$r['lat_wgs84']??null;$lon=$r['lng_wgs84']??null;if(!is_numeric($lat)||!is_numeric($lon)||abs((float)$lat)>90||abs((float)$lon)>180||((float)$lat===0.0&&(float)$lon===0.0))return null;return ['type'=>'Point','coordinates'=>[(float)$lon,(float)$lat]];}
 $g=json_decode($r['geometry_json'],true);if(!is_array($g)||!in_array($g['type']??'',['Polygon','MultiPolygon'],true))return null;return ['type'=>$g['type'],'coordinates'=>$g['coordinates']??[]];
}
function pmPhotos(mysqli $db,string $kind,array $r):array{
 if($kind==='areas')return pmQuery($db,'SELECT id,original_name,mime,content,file_path,note FROM svm_area_photos WHERE project_id=? AND area_id=? ORDER BY id',[$r['project_id'],$r['id']])->get_result()->fetch_all(MYSQLI_ASSOC);
 $rows=pmQuery($db,"SELECT url,note_photo AS note FROM trees_photo WHERE tree_id=? AND status='active' ORDER BY sort_order,id",[$r['id']])->get_result()->fetch_all(MYSQLI_ASSOC);if(!$rows&&!empty($r['photo_url']))$rows[]=['url'=>$r['photo_url'],'note'=>''];return $rows;
}
function pmPhotoBytes(string $kind,array $photo):array{
 if($kind==='areas'){require_once __DIR__.'/area_photo_storage.php';$bytes=apsRead($photo);}else{
  $parts=parse_url($photo['url']);if($parts===false||isset($parts['user'])||isset($parts['pass'])||(isset($parts['host'])&&strtolower($parts['host'])!==strtolower((string)parse_url(svmUrl(),PHP_URL_HOST))))throw new RuntimeException('Fotografie není dostupná.',404);
  $path=rawurldecode($parts['path']??'');if(str_contains($path,"\0")||str_contains($path,'\\'))throw new RuntimeException('Fotografie není dostupná.',404);if(str_starts_with($path,'/map/public/'))$path=substr($path,12);$path=ltrim($path,'/');$folder=explode('/',$path)[0];if(!in_array($folder,['uploads','photos'],true))throw new RuntimeException('Fotografie není dostupná.',404);
  $root=realpath(dirname(__DIR__).'/map/public/'.$folder);$f=realpath(dirname(__DIR__).'/map/public/'.$path);if(!$root||!$f||!str_starts_with($f,$root.'/')||!is_file($f)||filesize($f)>10*1024*1024)throw new RuntimeException('Fotografie není dostupná.',404);$bytes=file_get_contents($f);
 }
 $mime=(new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true))throw new RuntimeException('Fotografie není dostupná.',404);return [$bytes,$mime];
}
function pmRate(mysqli $db,int $project):void{
 // REMOTE_ADDR only: forwarded headers are user controlled on some hosts.
 $secret=hash('sha256',svmConfig()['database']['password'].'|'.svmConfig()['database']['name']);$hour=(string)intdiv(time(),3600);$keys=[hash_hmac('sha256','ip|'.($_SERVER['REMOTE_ADDR']??'unknown').'|'.$hour,$secret)=>5,hash_hmac('sha256','project|'.$project.'|'.$hour,$secret)=>50];ksort($keys);
 foreach($keys as $key=>$limit){pmQuery($db,'INSERT IGNORE INTO svm_public_rate(bucket,hits,expires_at) VALUES(?,0,DATE_ADD(NOW(),INTERVAL 2 HOUR))',[$key]);$r=pmQuery($db,'SELECT hits FROM svm_public_rate WHERE bucket=? FOR UPDATE',[$key])->get_result()->fetch_assoc();if((int)$r['hits']>=$limit)throw new RuntimeException('Příliš mnoho podnětů. Zkuste to později.',429);pmQuery($db,'UPDATE svm_public_rate SET hits=hits+1 WHERE bucket=?',[$key]);}
 pmQuery($db,'DELETE FROM svm_public_rate WHERE expires_at<NOW() LIMIT 100');
}
function pmNotify(mysqli $db,array $project):void{
 if(!$project['email_notify'])return;
 pmQuery($db,'UPDATE svm_public_maps SET last_mail_at=NOW() WHERE project_id=? AND (last_mail_at IS NULL OR last_mail_at<DATE_SUB(NOW(),INTERVAL 1 HOUR))',[$project['project_id']]);if(!$db->affected_rows)return;
 $rows=pmQuery($db,"SELECT DISTINCT u.email FROM users u JOIN projects p ON p.id=? LEFT JOIN project_users pu ON pu.project_id=p.id AND pu.user_id=u.id WHERE u.active=1 AND (p.owner_id=u.id OR pu.project_role='admin') LIMIT 20",[$project['project_id']])->get_result()->fetch_all(MYSQLI_ASSOC);
 require_once __DIR__.'/mail/send_mail.php';foreach($rows as $r)if(filter_var($r['email'],FILTER_VALIDATE_EMAIL))sendAppMail($r['email'],'Nové podněty ve veřejné mapě','<p>Ve vašem projektu přibyly podněty obyvatel. Přihlaste se do aplikace, vyberte projekt a otevřete Veřejná mapa a podněty.</p>');
}


function pmAreaEvent(array $r,array $fields):?array {
 if(!in_array('status',$fields,true))return null;
 $a=json_decode($r['attributes_json']??'{}',true);
 return ($a['status']??'')==='removed'?['label'=>'Odstraněný porost','date'=>null]:null;
}
function pmTreeEvent(array $r,array $fields):?array {
 require_once __DIR__.'/tree_lifecycle.php';
 $safe=array_intersect_key($r,array_flip($fields));
 return svmTreeEvent($safe);
}
