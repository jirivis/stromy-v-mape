<?php
require_once dirname(__DIR__).'/auth.php';require_once dirname(__DIR__).'/app/area_photo_storage.php';
if((int)($_SESSION['is_super_admin']??0)!==1){http_response_code(403);exit('Pouze správce systému.');}
header('Cache-Control: no-store');$_SESSION['photo_migration_csrf']??=bin2hex(random_bytes(32));
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 header('Content-Type: application/json; charset=utf-8');$tx=false;
 try{
  if(!hash_equals($_SESSION['photo_migration_csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Obnovte stránku.');
  if(($_POST['backup']??'')!=='yes')throw new RuntimeException('Nejprve potvrďte zálohu databáze a souborů.');
  $columns=$mysqli->query('SHOW COLUMNS FROM svm_area_photos')->fetch_all(MYSQLI_ASSOC);$names=array_column($columns,'Field');
  if(!in_array('file_path',$names,true))$mysqli->query('ALTER TABLE svm_area_photos ADD COLUMN file_path VARCHAR(80) NULL');
  foreach($columns as $c)if($c['Field']==='content'&&$c['Null']==='NO')$mysqli->query('ALTER TABLE svm_area_photos MODIFY content MEDIUMBLOB NULL');
  $done=0;
  for($i=0;$i<5;$i++){
   $mysqli->begin_transaction();$tx=true;$row=$mysqli->query('SELECT id,mime,content,file_path FROM svm_area_photos WHERE content IS NOT NULL ORDER BY id LIMIT 1 FOR UPDATE')->fetch_assoc();
   if(!$row){$mysqli->commit();$tx=false;break;}
   $key=apsPut($row['content'],$row['mime']);$verified=apsRead(['file_path'=>$key]);
   if(!hash_equals(hash('sha256',$row['content']),hash('sha256',$verified)))throw new RuntimeException('Kontrola kopie selhala. Originál zůstává v databázi.');
   $id=(int)$row['id'];$s=$mysqli->prepare('UPDATE svm_area_photos SET file_path=?,content=NULL WHERE id=?');$s->bind_param('si',$key,$id);$s->execute();$s->close();$mysqli->commit();$tx=false;$done++;
  }
  $remaining=(int)$mysqli->query('SELECT COUNT(*) AS n FROM svm_area_photos WHERE content IS NOT NULL')->fetch_assoc()['n'];
  echo json_encode(['done'=>$done,'remaining'=>$remaining]);
 }catch(Throwable $e){if($tx)$mysqli->rollback();error_log('Photo migration: '.$e->getMessage());http_response_code(422);echo json_encode(['error'=>'Převod byl zastaven. Již dokončené fotografie zůstávají převedené, ostatní v databázi. Podrobnosti jsou v PHP logu.']);}exit;
}
?><!doctype html><html lang="cs"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Převod fotografií</title><link rel="stylesheet" href="../map/public/project_tools/tools.css"><main><a href="../dashboard.php">Dashboard</a><h1>Převod fotografií ploch do souborů</h1><p>Pro existující instalaci této verze. Nová instalace už ukládá fotografie do souborů. Každá kopie se zkontroluje pomocí SHA-256 před odstraněním obrazových dat z databáze. ID, popisky a vazby zůstanou zachovány.</p><p>Během převodu neprovádějte úpravy fotografií. Při přerušení lze pokračovat. Zálohujte databázi i private/storage.</p><form id="migration"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['photo_migration_csrf'])?>"><label><input type="checkbox" name="backup" value="yes" required> Mám zálohu databáze i souborů.</label><button>Spustit převod</button></form><p id="status" role="status"></p></main><script src="area_photos.js"></script></html>
