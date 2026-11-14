<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/app/public_map.php';
require_once dirname(__DIR__).'/api/db.php';
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');
function pmOut(array $data,int $status=200):never{http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;}
$tx=false;
try{
 $p=pmProject($mysqli,(string)($_GET['p']??''));$action=$_GET['action']??'map';
 if($action==='map'&&$_SERVER['REQUEST_METHOD']==='GET'){
  $features=[];foreach(['trees','areas'] as $kind){$table=$kind==='trees'?'trees':'svm_areas';$cols='t.*';$res=pmQuery($mysqli,'SELECT o.token AS public_token,'.$cols.' FROM svm_public_objects o JOIN '.$table.' t ON t.id=o.object_id AND t.project_id=o.project_id WHERE o.project_id=? AND o.kind=? AND o.enabled=1',[$p['project_id'],$kind])->get_result();while($r=$res->fetch_assoc()){$g=pmGeometry($kind,$r);if($g)$features[]=['type'=>'Feature','geometry'=>$g,'properties'=>['token'=>$r['public_token'],'kind'=>$kind,'code'=>(string)$r[$kind==='trees'?'usr_id':'code'],'values'=>pmValues($kind,$r,$p['fields'][$kind])]];}}
  pmOut(['name'=>$p['name'],'type'=>'FeatureCollection','features'=>$features]);
 }
 [$o,$r]=pmObject($mysqli,$p,(string)($_GET['object']??''));$kind=$o['kind'];
 if($action==='detail'&&$_SERVER['REQUEST_METHOD']==='GET'){
  $photos=[];if($p['photos'])foreach(pmPhotos($mysqli,$kind,$r) as $i=>$photo)$photos[]=['url'=>'api.php?action=photo&p='.$p['token'].'&object='.$o['token'].'&index='.$i,'note'=>$p['photo_notes']?(string)($photo['note']??''):''];
  pmOut(['kind'=>$kind,'code'=>(string)$r[$kind==='trees'?'usr_id':'code'],'values'=>pmValues($kind,$r,$p['fields'][$kind]),'photos'=>$photos,'reports'=>(bool)$p['reports']]);
 }
 if($action==='photo'&&$_SERVER['REQUEST_METHOD']==='GET'){
  if(!$p['photos'])throw new RuntimeException('Fotografie není veřejná.',404);$index=filter_var($_GET['index']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);$photos=pmPhotos($mysqli,$kind,$r);if($index===false||!isset($photos[$index]))throw new RuntimeException('Fotografie není dostupná.',404);[$bytes,$mime]=pmPhotoBytes($kind,$photos[$index]);header('Content-Type: '.$mime);header('Content-Length: '.strlen($bytes));echo $bytes;exit;
 }
 if(!$p['reports'])throw new RuntimeException('Podněty nejsou zapnuté.',404);
 if(session_status()!==PHP_SESSION_ACTIVE)session_start(['cookie_httponly'=>true,'cookie_secure'=>str_starts_with(svmUrl(),'https://'),'cookie_samesite'=>'Lax']);
 if($action==='challenge'&&$_SERVER['REQUEST_METHOD']==='GET'){$_SESSION['public_report']=['token'=>bin2hex(random_bytes(24)),'project'=>$p['token'],'object'=>$o['token'],'time'=>time()];pmOut(['token'=>$_SESSION['public_report']['token']]);}
 if($action!=='report'||$_SERVER['REQUEST_METHOD']!=='POST')throw new RuntimeException('Neplatný požadavek.',405);
 $raw=file_get_contents('php://input',false,null,0,12001);if(strlen($raw)>12000)throw new InvalidArgumentException('Podnět je příliš dlouhý.');$d=json_decode($raw,true,16,JSON_THROW_ON_ERROR);$c=$_SESSION['public_report']??[];
 if(!is_string($d['token']??null)||!isset($c['token'])||!hash_equals($c['token'],$d['token'])||$c['project']!==$p['token']||$c['object']!==$o['token']||time()-$c['time']>3600)throw new RuntimeException('Formulář vypršel. Otevřete jej znovu.',403);
 if(time()-$c['time']<2)throw new RuntimeException('Před odesláním chvíli počkejte.',429);
 foreach(['message','contact','website'] as $k)if(!is_string($d[$k]??null))throw new InvalidArgumentException('Neplatný formulář.');
 $message=trim($d['message']);$contact=trim($d['contact']);if($d['website']!==''||mb_strlen($message)<10||mb_strlen($message)>2000||mb_strlen($contact)>200||($contact!==''&&!filter_var($contact,FILTER_VALIDATE_EMAIL)))throw new InvalidArgumentException('Napište 10 až 2000 znaků a případně platný kontaktní e-mail.');
 $mysqli->begin_transaction();$tx=true;
 // Lock publication settings so revocation cannot race the report write.
 $locked=pmQuery($mysqli,'SELECT enabled,reports FROM svm_public_maps WHERE project_id=? FOR UPDATE',[$p['project_id']])->get_result()->fetch_assoc();$object=pmQuery($mysqli,'SELECT enabled FROM svm_public_objects WHERE token=? FOR UPDATE',[$o['token']])->get_result()->fetch_assoc();if(!$locked['enabled']||!$locked['reports']||!$object['enabled'])throw new RuntimeException('Zveřejnění bylo zrušeno.',409);
 pmRate($mysqli,(int)$p['project_id']);pmQuery($mysqli,'INSERT INTO svm_public_reports(project_id,kind,object_id,object_code,message,contact) VALUES(?,?,?,?,?,?)',[$p['project_id'],$kind,$r['id'],(string)$r[$kind==='trees'?'usr_id':'code'],$message,$contact]);$mysqli->commit();$tx=false;unset($_SESSION['public_report']);session_write_close();
 try{pmNotify($mysqli,$p);}catch(Throwable $e){error_log('Public report notification failed');}pmOut(['ok'=>true]);
}catch(Throwable $e){if($tx)$mysqli->rollback();$status=in_array($e->getCode(),[403,404,405,409,429],true)?$e->getCode():($e instanceof InvalidArgumentException||$e instanceof JsonException?422:503);if($status===503)error_log('Public map: '.$e->getMessage());pmOut(['error'=>$status===503?'Veřejná mapa je dočasně nedostupná.':$e->getMessage()],$status);}
