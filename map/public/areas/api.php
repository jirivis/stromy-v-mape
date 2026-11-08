<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/app/area_photo_storage.php';
require_once __DIR__.'/../../../auth_api.php';
require_once __DIR__.'/../api/db.php';
require_once __DIR__.'/validation.php';
require_once __DIR__.'/history.php';
header('Cache-Control: no-store');
header('Content-Type: application/json; charset=utf-8');
function answer(array $data,int $status=200): never { http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit; }
$project=(int)($activeProjectId??0);
if($project<=0) answer(['error'=>'Vyberte aktivní projekt.'],403);
$mysqli->set_charset('utf8mb4');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$canEdit=in_array($activeProjectRole??'', ['owner','admin','editor'],true);
$action=$_GET['action']??'list';
$areaTransaction=false;
function areaQuery(string $sql,string $types='',array $args=[]): mysqli_stmt {
    global $mysqli;$s=$mysqli->prepare($sql);if($types!=='')$s->bind_param($types,...$args);$s->execute();return $s;
}
function ownedArea(int $id): array {
    global $project;$a=areaQuery('SELECT * FROM svm_areas WHERE id=? AND project_id=?','ii',[$id,$project])->get_result()->fetch_assoc();
    if(!$a) answer(['error'=>'Plocha v aktuálním projektu nebyla nalezena.'],404);return $a;
}
function asFeature(array $r): array {
    return ['type'=>'Feature','id'=>(int)$r['id'],'geometry'=>json_decode($r['geometry_json'],true,512,JSON_THROW_ON_ERROR),'properties'=>['id'=>(int)$r['id'],'code'=>$r['code'],'area_m2'=>(float)$r['area_m2'],'version'=>(int)$r['version'],'attributes'=>json_decode($r['attributes_json'],true,512,JSON_THROW_ON_ERROR),'created_at'=>$r['created_at'],'updated_at'=>$r['updated_at']]];
}
try {
    if($_SERVER['REQUEST_METHOD']==='GET') {
        if($action==='list') {
            $rows=areaQuery('SELECT * FROM svm_areas WHERE project_id=? ORDER BY code','i',[$project])->get_result();$features=[];
            while($r=$rows->fetch_assoc())$features[]=asFeature($r);
            answer(['type'=>'FeatureCollection','features'=>$features,'project_id'=>$project,'can_edit'=>$canEdit]);
        }
        if($action==='history') {
            $id=(int)($_GET['id']??0);ownedArea($id);$before=(int)($_GET['before']??0);
            $rows=areaQuery('SELECT h.id,h.action,h.snapshot_json,h.created_at,h.user_id,u.username FROM svm_area_history h LEFT JOIN users u ON u.id=h.user_id WHERE h.project_id=? AND h.area_id=?'.($before>0?' AND h.id<?':'').' ORDER BY h.id DESC LIMIT 21',$before>0?'iii':'ii',$before>0?[$project,$id,$before]:[$project,$id])->get_result()->fetch_all(MYSQLI_ASSOC);
            $more=count($rows)>20;$rows=array_slice($rows,0,20);foreach($rows as &$r){$r['snapshot']=json_decode($r['snapshot_json'],true);unset($r['snapshot_json']);}unset($r);answer(['rows'=>$rows,'more'=>$more,'project_id'=>$project]);
        }
        if($action==='get') {
            $id=(int)($_GET['id']??0);$r=ownedArea($id);
            $notesAvailable=true;try{
            $photos=areaQuery('SELECT id, original_name, created_at, note FROM svm_area_photos WHERE area_id=? AND project_id=? ORDER BY id','ii',[$id,$project])->get_result()->fetch_all(MYSQLI_ASSOC);
            }catch(mysqli_sql_exception $e){if((int)$e->getCode()!==1054)throw $e;$notesAvailable=false;$photos=areaQuery("SELECT id, original_name, created_at, '' AS note FROM svm_area_photos WHERE area_id=? AND project_id=? ORDER BY id",'ii',[$id,$project])->get_result()->fetch_all(MYSQLI_ASSOC);}
            answer(['feature'=>asFeature($r),'photos'=>$photos,'photo_notes_available'=>$notesAvailable,'project_id'=>$project]);
        }
        if($action==='photo') {
            $r=areaQuery('SELECT p.mime,p.content,p.file_path FROM svm_area_photos p JOIN svm_areas a ON a.id=p.area_id AND a.project_id=p.project_id WHERE p.id=? AND p.project_id=?','ii',[(int)($_GET['id']??0),$project])->get_result()->fetch_assoc();
            if(!$r)answer(['error'=>'Fotografie nebyla nalezena.'],404);
            header('Content-Type: '.$r['mime']);header('X-Content-Type-Options: nosniff');header('Content-Disposition: inline');echo apsRead($r);exit;
        }
        answer(['error'=>'Neznámá operace.'],404);
    }
    if($_SERVER['REQUEST_METHOD']!=='POST')answer(['error'=>'Použijte POST.'],405);
    requireProjectRole(['owner','admin','editor']);
    if(!$canEdit)answer(['error'=>'Nemáte oprávnění k úpravám.'],403);
    $token=$_SERVER['HTTP_X_CSRF_TOKEN']??'';
    if(!is_string($token) || empty($_SESSION['areas_csrf']) || !hash_equals($_SESSION['areas_csrf'],$token))answer(['error'=>'Platnost formuláře vypršela. Obnovte stránku.'],403);
    if((int)($_SERVER['HTTP_X_PROJECT_ID']??0)!==$project)answer(['error'=>'Aktivní projekt se změnil. Otevřete plochy znovu.'],409);
    if($action==='save') {
        $raw=file_get_contents('php://input',false,null,0,500001);
        if(strlen($raw)>500000)answer(['error'=>'Záznam je příliš velký.'],413);
        $d=json_decode($raw,true,64,JSON_THROW_ON_ERROR);
        if(!is_array($d))throw new InvalidArgumentException('Neplatný požadavek.');
        $id=(int)($d['id']??0);$version=(int)($d['version']??0);
        $code=areaText($d['code']??'',40);
        if(!preg_match('/^[\p{L}\p{N}_. -]{1,40}$/u',$code))throw new InvalidArgumentException('Vyplňte číslo plochy, například P1 (písmena, čísla, mezera, tečka, pomlčka).');
        [$g,$m2]=areaGeometry($d['geometry']??null);$a=areaAttributes($d['attributes']??null);
        $gj=json_encode($g,JSON_THROW_ON_ERROR);$aj=json_encode($a,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $mysqli->begin_transaction();$areaTransaction=true;
        if($id>0) {
            $old=areaQuery('SELECT * FROM svm_areas WHERE id=? AND project_id=? FOR UPDATE','ii',[$id,$project])->get_result()->fetch_assoc();
            if(!$old)throw new InvalidArgumentException('Plocha nebyla nalezena.');
            if((int)$old['version']!==$version){$mysqli->rollback();$areaTransaction=false;answer(['error'=>'Plochu mezitím změnil jiný uživatel. Obnovte ji před další úpravou.'],409);}
            areaHistoryBaseline($mysqli,$old);
            $s=areaQuery('UPDATE svm_areas SET code=?,geometry_json=?,attributes_json=?,area_m2=?,version=version+1 WHERE id=? AND project_id=? AND version=?','sssdiii',[$code,$gj,$aj,$m2,$id,$project,$version]);
            if($s->affected_rows!==1)throw new RuntimeException('Konflikt verze plochy.');
        } else {
            areaQuery('INSERT INTO svm_areas(project_id,code,geometry_json,attributes_json,area_m2) VALUES(?,?,?,?,?)','isssd',[$project,$code,$gj,$aj,$m2]);$id=(int)$mysqli->insert_id;
        }
        $saved=ownedArea($id);areaHistoryRecord($mysqli,$saved,($d['history_snapshot']??false)===true?'manual':(isset($old)?'updated':'created'));$mysqli->commit();$areaTransaction=false;answer(['feature'=>asFeature($saved)]);
    }
    if($action==='delete') {
        $d=json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR);
        $id=(int)($d['id']??0);$version=(int)($d['version']??0);
        $mysqli->begin_transaction();$areaTransaction=true;
        $row=areaQuery('SELECT version FROM svm_areas WHERE id=? AND project_id=? FOR UPDATE','ii',[$id,$project])->get_result()->fetch_assoc();
        if(!$row){$mysqli->rollback();$areaTransaction=false;answer(['error'=>'Plocha v aktuálním projektu nebyla nalezena.'],404);}
        if((int)$row['version']!==$version){$mysqli->rollback();$areaTransaction=false;answer(['error'=>'Plochu mezitím někdo změnil. Před smazáním ji znovu načtěte.'],409);}
        areaQuery('DELETE FROM svm_area_history WHERE area_id=? AND project_id=?','ii',[$id,$project]);
        areaQuery('DELETE FROM svm_area_photos WHERE area_id=? AND project_id=?','ii',[$id,$project]);
        areaQuery('DELETE FROM svm_areas WHERE id=? AND project_id=?','ii',[$id,$project]);
        $mysqli->commit();$areaTransaction=false;answer(['ok'=>true]);
    }
    if($action==='upload') {
        $id=(int)($_POST['area_id']??0);ownedArea($id);$f=$_FILES['photo']??null;
        if(!$f || is_array($f['error']) || $f['error']!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Fotografie se nenahrála. Zkontrolujte limit upload_max_filesize a post_max_size hostingu.');
        if($f['size']>5*1024*1024 || !is_uploaded_file($f['tmp_name']))throw new InvalidArgumentException('Fotografie může mít nejvýše 5 MB.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if(!in_array($mime,['image/jpeg','image/png','image/webp'],true) || !getimagesize($f['tmp_name']))throw new InvalidArgumentException('Použijte fotografii JPG, PNG nebo WebP.');
        $chars=preg_split('//u',basename($f['name']),-1,PREG_SPLIT_NO_EMPTY);$name=$chars===false?'fotografie':implode('',array_slice($chars,0,180));$bytes=file_get_contents($f['tmp_name']);
        // Fotografie jsou neveřejné a dostupné pouze přes kontrolu projektových práv.
        $mysqli->begin_transaction();$areaTransaction=true;
        $locked=areaQuery('SELECT id FROM svm_areas WHERE id=? AND project_id=? FOR UPDATE','ii',[$id,$project])->get_result()->fetch_assoc();
        if(!$locked){$mysqli->rollback();$areaTransaction=false;answer(['error'=>'Plocha již neexistuje.'],404);}
        $fileKey=apsPut($bytes,$mime);
        $stmt=$mysqli->prepare('INSERT INTO svm_area_photos(area_id,project_id,original_name,mime,file_path) VALUES(?,?,?,?,?)');
        $stmt->bind_param('iisss',$id,$project,$name,$mime,$fileKey);$stmt->execute();
        $photoId=(int)$mysqli->insert_id;$mysqli->commit();$areaTransaction=false;answer(['id'=>$photoId]);
    }
    if($action==='photo_note') {
        $raw=file_get_contents('php://input',false,null,0,16001);if(strlen($raw)>16000)answer(['error'=>'Příliš dlouhý popisek.'],413);$d=json_decode($raw,true,16,JSON_THROW_ON_ERROR);$id=(int)($d['id']??0);$note=$d['note']??null;$old=$d['previous']??null;
        if(!is_string($note)||!is_string($old)||mb_strlen($note)>1000)answer(['error'=>'Popisek může mít nejvýše 1000 znaků.'],422);
        $mysqli->begin_transaction();$areaTransaction=true;
        $photo=areaQuery('SELECT p.note FROM svm_area_photos p JOIN svm_areas a ON a.id=p.area_id AND a.project_id=p.project_id WHERE p.id=? AND p.project_id=? FOR UPDATE','ii',[$id,$project])->get_result()->fetch_assoc();
        if(!$photo){$mysqli->rollback();$areaTransaction=false;answer(['error'=>'Fotografie nebyla nalezena.'],404);}
        if($photo['note']!==$old){$mysqli->rollback();$areaTransaction=false;answer(['error'=>'Popisek mezitím někdo změnil. Obnovte fotografii.'],409);}
        areaQuery('UPDATE svm_area_photos SET note=? WHERE id=? AND project_id=?','sii',[$note,$id,$project]);$mysqli->commit();$areaTransaction=false;answer(['ok'=>true,'note'=>$note]);
    }
    if($action==='remove_photo') {
        $d=json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR);$id=(int)($d['id']??0);
        areaQuery('DELETE p FROM svm_area_photos p JOIN svm_areas a ON a.id=p.area_id AND a.project_id=p.project_id WHERE p.id=? AND p.project_id=?','ii',[$id,$project]);answer(['ok'=>true]);
    }
    answer(['error'=>'Neznámá operace.'],404);
} catch(InvalidArgumentException|JsonException $e) {if($areaTransaction)$mysqli->rollback();answer(['error'=>$e->getMessage()],422);
} catch(mysqli_sql_exception $e) {
    if($areaTransaction)$mysqli->rollback();
    error_log('SVM areas: '.$e->getMessage());
    if((int)$e->getCode()===1054)answer(['error'=>'V databázi chybí potřebný sloupec. U fotografií ploch spusťte 05_popisky_fotek_ploch.sql. Pokud chyba trvá, zkontrolujte serverový protokol.'],503);
    if((int)$e->getCode()===1062)answer(['error'=>'Toto číslo plochy už v projektu existuje.'],409);
    if((int)$e->getCode()===1146)answer(['error'=>'Plochy ještě nejsou připravené v databázi. Ověřte import 01_plochy.sql a nové migrace 04_historie_ploch.sql.'],503);
    answer(['error'=>'Uložení nebo načtení selhalo. Zkontrolujte serverový protokol.'],500);
} catch(Throwable $e) {if($areaTransaction)$mysqli->rollback();error_log('SVM areas: '.$e->getMessage());answer(['error'=>'Operaci se nepodařilo dokončit.'],500);}
