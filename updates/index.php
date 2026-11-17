<?php
declare(strict_types=1);
define('SVM_UPDATE_ENTRY',true);
require_once dirname(__DIR__).'/auth.php';require_once dirname(__DIR__).'/app/update.php';
if((int)($_SESSION['is_super_admin']??0)!==1){http_response_code(403);exit('Aktualizace může spravovat pouze správce systému.');}
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
$_SESSION['update_csrf']??=bin2hex(random_bytes(32));$cfg=upConfig(svmConfig()['updates']??[]);$message='';$error='';$pending=null;
try{
 if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
  if(!hash_equals($_SESSION['update_csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Obnovte stránku. Platnost formuláře vypršela.');
  $action=$_POST['action']??'';
  if($action==='source'){upSaveSource((string)($_POST['repository']??''),($_POST['automatic']??'')==='yes');$cfg=upConfig(svmConfig()['updates']??[]);$message='Zdroj byl uložen. Dostupnost ověřte tlačítkem Zkontrolovat vydání. Podpisový klíč zůstává beze změny.';}
  elseif($action==='check'){upCheck($cfg);$message='Informace o vydání byly obnoveny.';}
  elseif($action==='prepare'||$action==='download'){
   $token=bin2hex(random_bytes(16));$path=upDir().'/pending-'.$token.'.zip';
   if($action==='download'){$release=upCheck($cfg);if(empty($release['url']))throw new RuntimeException('Není dostupný kompatibilní balíček.');$bytes=upFetch($release['url'],30*1024*1024);if(!hash_equals($release['sha256'],hash('sha256',$bytes)))throw new RuntimeException('Stažený balíček má nesprávný kontrolní součet.');upAtomic($path,$bytes);}
   else{if(($_FILES['package']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file($_FILES['package']['tmp_name']))throw new RuntimeException('Vyberte aktualizační ZIP v limitu hostingu.');if(!move_uploaded_file($_FILES['package']['tmp_name'],$path))throw new RuntimeException('Balíček nelze uložit.');chmod($path,0600);}
   try{$pending=upInspect($path,(string)($cfg['public_key']??''),upVersion());}catch(Throwable $e){unlink($path);throw $e;}
   if(isset($_SESSION['update_pending']))@unlink(upDir().'/pending-'.$_SESSION['update_pending'].'.zip');
   $_SESSION['update_pending']=$token;$_SESSION['update_pending_hash']=hash_file('sha256',$path);$message='Podpis a obsah balíčku jsou ověřeny. Zatím nebylo nic nainstalováno.';
  }elseif(in_array($action,['install','database_sql','database_check'],true)){
   $token=(string)($_SESSION['update_pending']??'');if(!preg_match('/^[a-f0-9]{32}$/D',$token))throw new RuntimeException('Nejprve připravte balíček.');
   if($action==='install'&&(($_POST['backup']??'')!=='yes'||($_POST['approve']??'')!=='yes'))throw new RuntimeException('Potvrďte zálohu a souhlas s instalací.');
   $path=upDir().'/pending-'.$token.'.zip';if(!is_file($path)||!hash_equals((string)($_SESSION['update_pending_hash']??''),(string)hash_file('sha256',$path)))throw new RuntimeException('Připravený balíček se změnil. Připravte jej znovu.');
   $pending=upInspect($path,(string)($cfg['public_key']??''),upVersion());
   if($action==='database_sql'){
    $sql=upMigrationSql($path,$pending);header('Content-Type: application/sql; charset=utf-8');header('Content-Disposition: attachment; filename="priprava-databaze.sql"');echo $sql;exit;
   }
   if($action==='database_check'){upDatabasePreflight($pending,$mysqli);$message='Požadovaná databázová struktura je připravená. Při instalaci se ověří znovu.';}
   else{
   $version=upApply($path,(string)($cfg['public_key']??''),$mysqli);unlink($path);unset($_SESSION['update_pending'],$_SESSION['update_pending_hash']);$pending=null;$message='Aktualizace na verzi '.$version.' byla dokončena.';
   }
  }else throw new RuntimeException('Neznámá akce.');
 }
 if(isset($_SESSION['update_pending']))$pending=upInspect(upDir().'/pending-'.$_SESSION['update_pending'].'.zip',(string)($cfg['public_key']??''),upVersion());
}catch(Throwable $e){$error=$e->getMessage();}
$databaseReady=true;$databaseMessage='';
if($pending){try{upDatabasePreflight($pending,$mysqli);}catch(Throwable $e){$databaseReady=false;$databaseMessage=$e->getMessage();}}
$release=upCachedRelease($cfg);
function uh($s):string{return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function ut():void{echo '<input type="hidden" name="csrf" value="'.uh($_SESSION['update_csrf']).'">';}
?><!doctype html><html lang="cs"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Aktualizace aplikace</title><link rel="stylesheet" href="../map/public/project_tools/tools.css"><main>
<a href="../dashboard.php">← Dashboard</a><h1>Aktualizace aplikace</h1><p>Nainstalovaná verze: <strong><?=uh(upVersion())?></strong></p>
<?php if($message):?><p role="status"><?=uh($message)?></p><?php endif;?><?php if($error):?><p role="alert"><?=uh($error)?></p><?php endif;?>
<?php if(is_file(upDir().'/maintenance.json')):?><p role="alert">Aplikace je v režimu údržby. Před pokračováním postupujte podle docs/AKTUALIZACE.md.</p><?php endif;?>
<details><summary>Nastavení zdroje aktualizací</summary>
<p>Veřejný distribuční repozitář musí mít release.json ve větvi main a podepsané balíčky v Releases. Soukromý repozitář bez přihlášení fungovat nebude.</p>
<form method="post"><?php ut();?><p><label>GitHub repozitář <input name="repository" value="<?=uh($cfg['repository']??'')?>" placeholder="vlastník/název" maxlength="200"></label></p><p><label><input type="checkbox" name="automatic" value="yes" <?=!empty($cfg['check_on_dashboard'])?'checked':''?>> Kontrolovat vydání při otevření dashboardu správce (nejvýše jednou za 6 hodin)</label></p><button name="action" value="source">Uložit zdroj</button></form>
<p>Instalace vždy vyžaduje váš souhlas. Změna zdroje nemění důvěryhodný podpisový klíč.</p></details>
<h2>Dostupné vydání</h2>
<?php if(empty($cfg['repository'])):?><p>GitHub repozitář ještě není nastaven. Automatická kontrola vydání není aktivní.</p><?php endif;?>
<?php if($release):?><p>Poslední zjištěná verze: <?=uh($release['version']??'')?> · kontrola <?=uh(date('d. m. Y H:i',(int)($release['checked_at']??0)))?></p><p><?=nl2br(uh($release['notes']??''))?></p><?php endif;?>
<form method="post"><?php ut();?><button name="action" value="check">Zkontrolovat vydání</button> <button name="action" value="download">Stáhnout a ověřit balíček</button></form>
<h2>Ruční nahrání</h2><p>Nahrání balíček pouze připraví. Přijímá se jen ZIP podepsaný nastaveným klíčem vydavatele.</p>
<form method="post" enctype="multipart/form-data"><?php ut();?><input type="file" name="package" accept=".zip" required><button name="action" value="prepare">Nahrát a ověřit</button></form>
<?php if($pending):?><h2>Připravená aktualizace <?=uh($pending['version'])?></h2><p><?=nl2br(uh($pending['notes']??''))?></p><p>Souborů: <?=count($pending['files'])?> · databázových migrací: <?=count($pending['migrations']??[])?></p><?php if(!empty($pending['migrations'])):?><h3>Příprava databáze</h3><p>Po záloze stáhněte SQL a spusťte jej v phpMyAdminu ve správné databázi pod účtem správce databáze. Jeho heslo do aplikace neukládejte. Před spuštěním přerušte práci uživatelů a obnovte ji až po dokončení aktualizace.</p><form method="post"><?php ut();?><button name="action" value="database_sql">Stáhnout SQL přípravu</button> <button name="action" value="database_check">Zkontrolovat databázi</button></form><?php endif;?>
<?php if(!$databaseReady):?><p role="alert"><?=uh($databaseMessage)?></p><?php else:?><form method="post"><?php ut();?><p><label><input type="checkbox" name="backup" value="yes" required> Mám aktuální zálohu databáze, konfigurace a fotografií.</label></p><p><label><input type="checkbox" name="approve" value="yes" required> Schvaluji instalaci této verze.</label></p><button name="action" value="install">Schválit a nainstalovat</button></form><?php endif;?><?php endif;?>
<p>Data projektů se na GitHub neodesílají. Konfigurace a fotografie se aktualizačním balíčkem nepřepisují.</p></main></html>
