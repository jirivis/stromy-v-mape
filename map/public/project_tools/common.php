<?php
declare(strict_types=1);
if(defined('PT_TEMPLATES_ONLY')) require_once __DIR__.'/../../../auth.php';
else require_once __DIR__.'/../../../auth_api.php';
require_once __DIR__.'/../api/db.php';
require_once __DIR__.'/../areas/validation.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$project=(int)($activeProjectId??0);
if($project<1&&!defined('PT_TEMPLATES_ONLY')){http_response_code(403);exit('Vyberte aktivní projekt.');}
$canEdit=in_array($activeProjectRole??'', ['owner','admin','editor'],true);
$canConfigure=in_array($activeProjectRole??'', ['owner','admin'],true);
$mysqli->set_charset('utf8mb4');
if(defined('PT_TEMPLATES_ONLY')){
 $project=0;$canEdit=false;$canConfigure=(int)($_SESSION['is_super_admin']??0)===1;
 if(!$canConfigure){$uid=(int)$_SESSION['user_id'];$q=$mysqli->prepare("SELECT p.id FROM projects p LEFT JOIN project_users pu ON pu.project_id=p.id AND pu.user_id=? WHERE p.owner_id=? OR pu.project_role IN ('owner','admin') LIMIT 1");$q->bind_param('ii',$uid,$uid);$q->execute();$canConfigure=(bool)$q->get_result()->fetch_assoc();}
}

mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$_SESSION['project_tools_csrf']??=bin2hex(random_bytes(32));
function ptQuery(string $sql,array $args=[]):mysqli_stmt{global $mysqli;$s=$mysqli->prepare($sql);if($args)$s->bind_param(str_repeat('s',count($args)),...$args);$s->execute();return $s;}
function ptJson($v):string{return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
function ptOut($v,int $code=200):never{http_response_code($code);header('Content-Type: application/json; charset=utf-8');echo ptJson($v);exit;}
function ptCheckPost():void{global $project;if($_SERVER['REQUEST_METHOD']!=='POST')throw new RuntimeException('Použijte POST.',405);if(!hash_equals($_SESSION['project_tools_csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??$_POST['token']??''))throw new RuntimeException('Obnovte stránku: platnost formuláře vypršela.',403);if(!defined('PT_TEMPLATES_ONLY')&&(int)($_SERVER['HTTP_X_PROJECT_ID']??$_POST['project_id']??0)!==$project)throw new RuntimeException('Aktivní projekt se změnil. Obnovte stránku.',409);}
require_once __DIR__.'/structure_catalog.php';
require_once __DIR__.'/coordinates.php';
function ptSettings():array{global $project;$r=ptQuery('SELECT * FROM svm_form_settings WHERE project_id=?',[$project])->get_result()->fetch_assoc();$d=$r?json_decode($r['settings_json'],true,64,JSON_THROW_ON_ERROR):[];return ['version'=>(int)($r['version']??0),'hidden'=>$d['hidden']??($d?:['trees'=>[],'areas'=>[]]),'profiles'=>$d['profiles']??[],'template_name'=>$d['template_name']??'Vlastní nastavení projektu'];}
function ptRows(string $kind,bool $lock=false):array{global $project;return ptQuery('SELECT * FROM '.($kind==='trees'?'trees':'svm_areas').' WHERE project_id=? ORDER BY id'.($lock?' FOR UPDATE':''),[$project])->get_result()->fetch_all(MYSQLI_ASSOC);}
function ptVersion(array $row):string{ksort($row);return hash('sha256',ptJson($row));}
function ptHead(string $title):void{header('Content-Type: text/html; charset=utf-8');echo '<!doctype html><html lang="cs"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.htmlspecialchars($title).'</title><link rel="stylesheet" href="tools.css?v=2.25.1"><header><a href="/dashboard.php">← Dashboard</a> <a href="../">Hlavní mapa</a><strong>'.htmlspecialchars($title).'</strong></header>';}
function ptBoot(array $extra=[]):void{global $project,$canEdit,$canConfigure;echo '<script>window.ProjectTools='.json_encode(array_merge(['project_id'=>$project,'csrf'=>$_SESSION['project_tools_csrf'],'can_edit'=>$canEdit,'can_configure'=>$canConfigure,'user_id'=>(int)$_SESSION['user_id']],$extra),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';</script>';}
function ptTableFields(string $kind,array $hidden):array {
 $fields=$kind==='trees'?ptTreeFields():ptAreaFields();
 $base=$kind==='trees'?['usr_id'=>['label'=>'Číslo stromu','type'=>'number']]:['code'=>['label'=>'Číslo plochy','readonly'=>true],'area_m2'=>['label'=>'Výměra (m²)','readonly'=>true]];
 if($kind==='trees'){
  $fields['status']=['label'=>'Stav záznamu','readonly'=>true];
  foreach(['lat_wgs84'=>'Šířka (WGS84)','lng_wgs84'=>'Délka (WGS84)','x_sjtsk'=>'X (S-JTSK)','y_sjtsk'=>'Y (S-JTSK)'] as $key=>$label)$fields[$key]=['label'=>$label,'readonly'=>true];
 }else{$fields['species']=['label'=>'Druhové složení','readonly'=>true];$fields['components']=['label'=>'Charaktery a výměry','readonly'=>true];}
 foreach($hidden as $key)unset($fields[$key]);$fields=array_replace($base,$fields);
 $order=$kind==='trees'?['usr_id','species','scientific_author','czech','angio_gymno','cultivated_variety','dbh1_cm','dbh2_cm','dbh3_cm','dbh4_cm','girth1_cm','girth2_cm','girth3_cm','girth4_cm','height','canopy_h_m','spread_m','phy_age','age','vitality','tree_health','stability','operational_safety','longevity','ecological_value','landscape_value','note_tree','treatment_u0','costs_tu0','treatment_u1','costs_tu1','treatment_u2','costs_tu2','treatment_u3','costs_tu3','note_treatment','treatment_history','planted','measured_at','last_insp_at','next_insp_at','lat_wgs84','lng_wgs84','y_sjtsk','x_sjtsk','note_public','status']:['code','name','status','area_m2','species','height_min','height_max','components','suitability','management','biology','attractiveness','state_note','treatments','urgency','note','planted','measured_at','last_insp_at','next_insp_at'];
 $out=[];foreach(array_unique([...$order,...array_keys($fields)]) as $key)if(isset($fields[$key])){$out[$key]=$fields[$key];$out[$key]['editable']=empty($fields[$key]['readonly']);}
 return $out;
}
function ptGeometry(string $kind,array $row):?array {
 if($kind==='trees'){$lat=$row['lat_wgs84']??null;$lng=$row['lng_wgs84']??null;if(!is_numeric($lat)||!is_numeric($lng)||!is_finite((float)$lat)||!is_finite((float)$lng)||abs((float)$lat)>90||abs((float)$lng)>180||((float)$lat===0.0&&(float)$lng===0.0))return ptJtskPoint($row['y_sjtsk']??null,$row['x_sjtsk']??null);return ['type'=>'Point','coordinates'=>[(float)$lng,(float)$lat]];}
 $g=json_decode($row['geometry_json']??'',true);if(!is_array($g)||!in_array($g['type']??'',['Polygon','MultiPolygon'],true)||empty($g['coordinates']))return null;$polygons=$g['type']==='Polygon'?[$g['coordinates']]:$g['coordinates'];foreach($polygons as $rings){if(!is_array($rings)||!count($rings))return null;foreach($rings as $ring){if(!is_array($ring)||count($ring)<4||$ring[0]!==$ring[count($ring)-1])return null;foreach($ring as $point){if(!is_array($point)||count($point)<2||!is_numeric($point[0])||!is_numeric($point[1])||!is_finite((float)$point[0])||!is_finite((float)$point[1])||abs((float)$point[0])>180||abs((float)$point[1])>90)return null;}}}return $g;
}
