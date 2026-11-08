<?php
declare(strict_types=1);
// A shared lock keeps an update from replacing files used by an active request.
if(PHP_SAPI!=='cli'&&!defined('SVM_UPDATE_ENTRY')){
 $svmAppLock=fopen(dirname(__DIR__).'/private/app.lock','c+');
 if(!$svmAppLock||!flock($svmAppLock,LOCK_SH|LOCK_NB)||is_file(dirname(__DIR__).'/private/updates/maintenance.json')){
  http_response_code(503);header('Retry-After: 60');header('Content-Type: text/plain; charset=utf-8');exit('Probíhá údržba aplikace. Zkuste to prosím později.');
 }
}

function svmConfig():array{
 static $config=null;if($config!==null)return $config;
 $path=dirname(__DIR__).'/private/config.php';
 if(!is_file($path))throw new RuntimeException('Chybí private/config.php. Připravte konfiguraci podle config.example.php.');
 $config=require $path;if(!is_array($config))throw new RuntimeException('Neplatná konfigurace aplikace.');return $config;
}
function svmUrl(string $path=''):string{
 $url=rtrim((string)(svmConfig()['app']['url']??''),'/');$p=parse_url($url);
 if(!$p||!in_array($p['scheme']??'',['https','http'],true)||empty($p['host'])||isset($p['user'])||isset($p['pass'])||isset($p['query'])||isset($p['fragment'])||!empty($p['path']))throw new RuntimeException('Zadejte adresu samostatné domény nebo subdomény bez podadresáře.');
 return $url.($path!==''?'/'.ltrim($path,'/'):'');
}
