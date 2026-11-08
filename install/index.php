<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';require_once __DIR__.'/core.php';
ini_set('display_errors','0');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'");
if(session_status()!==PHP_SESSION_ACTIVE)session_start();$_SESSION['install_csrf']??=bin2hex(random_bytes(32));
$checks=siRequirements();$checks['HTTPS pro instalaci']=(!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off')||in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);$error='';$success=false;$config=null;$locked=is_file(dirname(__DIR__).'/private/installed.lock');
try{$config=svmConfig();svmUrl();}catch(Throwable $e){$error='Připravte private/config.php podle vzoru a zadejte adresu samostatné domény nebo subdomény.';}
$setupKey=(string)($config['installation_key']??'');$ready=$config&&strlen($setupKey)>=32&&!in_array(false,$checks,true)&&!$locked;
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 $lock=null;try{
  if(!$ready)throw new RuntimeException('Instalace není povolená. Zkontrolujte konfiguraci, požadavky a instalační klíč.');
  if(!hash_equals($_SESSION['install_csrf'],(string)($_POST['csrf']??''))||!hash_equals($setupKey,(string)($_POST['installation_key']??'')))throw new RuntimeException('Neplatný instalační klíč nebo formulář.');
  if(($_POST['confirm_empty']??'')!=='yes')throw new RuntimeException('Potvrďte použití nové prázdné databáze.');
  $admin=siAdmin($_POST);$lock=fopen(dirname(__DIR__).'/private/install.mutex','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Jiná instalace právě probíhá.');
  if(is_file(dirname(__DIR__).'/private/installed.lock'))throw new RuntimeException('Instalace již byla dokončena.');
  mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$c=$config['database'];$db=new mysqli($c['host'],$c['user'],$c['password'],$c['name'],(int)($c['port']??3306));$db->set_charset('utf8mb4');
  siInstall($db,file_get_contents(dirname(__DIR__).'/database/schema.sql'),$admin);$db->close();
  if(file_put_contents(dirname(__DIR__).'/private/installed.lock',date(DATE_ATOM)."\n",LOCK_EX)===false)throw new RuntimeException('Správce byl vytvořen, ale uzamčení souborem selhalo. Smažte instalační klíč z konfigurace a složku install.');
  unset($_SESSION['install_csrf']);$success=true;$ready=false;
 }catch(Throwable $e){error_log('Installation: '.$e->getMessage());$error=$e instanceof mysqli_sql_exception?'Databázová operace selhala. Podrobnosti jsou v serverovém logu. Část tabulek již mohla vzniknout; opakovaná instalace je do neprázdné databáze zakázaná.':$e->getMessage();}
 finally{if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}}
}
function h($s):string{return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?><!doctype html><html lang="cs"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Instalace aplikace</title><link rel="stylesheet" href="../map/public/project_tools/tools.css"><main><h1>Instalace Stromy v mapě</h1>
<?php if($success): ?><p>Instalace dokončena. Správce je vytvořen. Vymažte installation_key z konfigurace a odstraňte složku install.</p><a href="../login.php">Přejít na přihlášení</a>
<?php elseif($locked): ?><p>Instalace již byla dokončena.</p>
<?php else: ?><p>Určeno výhradně pro novou prázdnou databázi. Tento instalátor neaktualizuje stávající web.</p><ul><?php foreach($checks as $label=>$ok): ?><li><?=h($label)?>: <?=$ok?'OK':'CHYBÍ'?></li><?php endforeach; ?></ul>
<?php if($error): ?><p role="alert"><?=h($error)?></p><?php endif; ?>
<?php if($config&&strlen($setupKey)<32): ?><p>Do private/config.php vložte vlastní náhodný installation_key o nejméně 32 znacích. Bez něj nelze vytvořit správce.</p><?php endif; ?>
<?php if($ready): ?><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['install_csrf'])?>"><p><label>Instalační klíč <input type="password" name="installation_key" required autocomplete="off"></label></p><p><label>Jméno správce <input name="username" required minlength="3" maxlength="50" autocomplete="username"></label></p><p><label>E-mail <input type="email" name="email" required maxlength="100"></label></p><p><label>Heslo (alespoň 12 znaků) <input type="password" name="password" required minlength="12" autocomplete="new-password"></label></p><p><label>Heslo znovu <input type="password" name="password_again" required autocomplete="new-password"></label></p><p><label><input type="checkbox" name="confirm_empty" value="yes" required> Používám novou prázdnou databázi.</label></p><button>Vytvořit databázové tabulky a správce</button></form><?php endif; ?><?php endif; ?></main></html>
