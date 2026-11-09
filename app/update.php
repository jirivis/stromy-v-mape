<?php
declare(strict_types=1);
// Update packages are executable code. Only signatures from the configured publisher are accepted.
function upRoot():string{return dirname(__DIR__);}
function upDir():string{$p=upRoot().'/private/updates';if(!is_dir($p)&&!mkdir($p,0700,true)&&!is_dir($p))throw new RuntimeException('Nelze vytvořit úložiště aktualizací.');return $p;}
function upVersion():string{return trim(file_get_contents(upRoot().'/app/VERSION'));}
function upPath(string $p):bool{
 if(!preg_match('~^[A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)*$~D',$p)||str_contains($p,'..'))return false;
 // Only these publisher-managed protection files may enter photo storage.
 if(in_array($p,['map/public/photos/.htaccess','map/public/uploads/.htaccess'],true))return true;
 foreach(['private','map/public/photos','map/public/uploads','.git','install'] as $x)if($p===$x||str_starts_with($p,$x.'/'))return false;
 return !in_array($p,['app/VERSION','release.json','manifest.json','signature.txt'],true);
}
function upAtomic(string $p,string $bytes,int $mode=0600):void{
 $dir=dirname($p);if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Nelze vytvořit cílovou složku.');
 $tmp=tempnam($dir,'.update-');if($tmp===false)throw new RuntimeException('Nelze připravit zápis.');
 try{if(file_put_contents($tmp,$bytes,LOCK_EX)!==strlen($bytes))throw new RuntimeException('Neúplný zápis souboru.');chmod($tmp,$mode);if(!rename($tmp,$p))throw new RuntimeException('Nelze dokončit zápis.');}finally{if(is_file($tmp))unlink($tmp);}
}
function upInspect(string $zipPath,string $publicKey,string $current):array{
 if(!extension_loaded('openssl')||!class_exists('ZipArchive'))throw new RuntimeException('Aktualizace vyžaduje OpenSSL a ZipArchive.');
 if(trim($publicKey)==='')throw new RuntimeException('Nejprve nastavte veřejný podpisový klíč vydavatele v konfiguraci.');
 if(!is_file($zipPath)||filesize($zipPath)>30*1024*1024)throw new RuntimeException('Balíček je příliš velký (nejvýše 30 MB).');
 $z=new ZipArchive();if($z->open($zipPath)!==true)throw new RuntimeException('Neplatný ZIP.');
 try{
  if($z->numFiles>2500)throw new RuntimeException('Příliš mnoho souborů.');$size=0;$seen=[];
  for($i=0;$i<$z->numFiles;$i++){$st=$z->statIndex($i);$n=$st['name'];if(isset($seen[$n]))throw new RuntimeException('Duplicitní soubor.');$seen[$n]=true;$size+=$st['size'];if($size>100*1024*1024||$st['size']>15*1024*1024)throw new RuntimeException('Rozbalený balíček je příliš velký.');$os=0;$attr=0;$z->getExternalAttributesIndex($i,$os,$attr);if(($attr>>16&0170000)===0120000)throw new RuntimeException('Symbolické odkazy nejsou povoleny.');}
  $raw=$z->getFromName('manifest.json');$sig=$z->getFromName('signature.txt');if($raw===false||strlen($raw)>512000||$sig===false||strlen($sig)>4096)throw new RuntimeException('Chybí manifest nebo podpis.');
  $decoded=base64_decode(trim($sig),true);if($decoded===false||openssl_verify($raw,$decoded,$publicKey,OPENSSL_ALGO_SHA256)!==1)throw new RuntimeException('Podpis balíčku není platný.');
  $m=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
  if(($m['product']??'')!=='stromy-v-mape'||!preg_match('/^\d+\.\d+\.\d+$/D',$m['version']??'')||!preg_match('/^\d+\.\d+\.\d+$/D',$m['from']??'')||$m['from']!==$current||!version_compare($m['version'],$current,'>'))throw new RuntimeException('Balíček neodpovídá nainstalované verzi nebo není novější.');
  if(version_compare(PHP_VERSION,$m['php_min']??'8.2','<'))throw new RuntimeException('Balíček vyžaduje novější PHP.');
  if(!is_array($m['files']??null)||!$m['files'])throw new RuntimeException('Prázdný balíček.');$expected=['manifest.json'=>true,'signature.txt'=>true];
  foreach($m['files'] as $p=>$hash){if(!is_string($p)||!upPath($p)||!is_string($hash)||!preg_match('/^[a-f0-9]{64}$/D',$hash))throw new RuntimeException('Nepovolená cesta nebo kontrolní součet.');$b=$z->getFromName('payload/'.$p);if($b===false||!hash_equals($hash,hash('sha256',$b)))throw new RuntimeException('Poškozený soubor balíčku.');$expected['payload/'.$p]=true;}
  if(array_diff_key($seen,$expected)||array_diff_key($expected,$seen))throw new RuntimeException('Balíček obsahuje neočekávané soubory.');
  if(isset($m['migrations'])&&!is_array($m['migrations']))throw new RuntimeException('Neplatný seznam databázových migrací.');
  foreach($m['migrations']??[] as $p)if(!is_string($p)||!str_starts_with($p,'database/migrations/')||!str_ends_with($p,'.sql')||!isset($m['files'][$p]))throw new RuntimeException('Neplatná migrace.');
  return $m;
 }finally{$z->close();}
}
function upSafeTarget(string $p):string{
 $root=upRoot();$parts=explode('/',$p);$walk=$root;foreach($parts as $part){$walk.='/'.$part;if(is_link($walk))throw new RuntimeException('Cíl obsahuje symbolický odkaz.');}return $walk;
}
require_once __DIR__.'/update_database.php';
function upApply(string $zipPath,string $key,mysqli $db):string{
 $lock=fopen(upRoot().'/private/app.lock','c+');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Aplikace právě obsluhuje požadavky. Zkuste aktualizaci za chvíli.');
 $dir=upDir();$started=false;$z=null;
 try{
  if(is_file($dir.'/maintenance.json'))throw new RuntimeException('Předchozí aktualizace vyžaduje kontrolu správce. Viz postup obnovy.');
  $m=upInspect($zipPath,$key,upVersion());upDatabasePreflight($m,$db);$id=gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));$backup=$dir.'/backup-'.$id;mkdir($backup,0700);
  $targets=array_keys($m['files']);$targets[]='app/VERSION';$existing=[];
  foreach($targets as $p){$target=upSafeTarget($p);if(is_dir($target))throw new RuntimeException('Cílový soubor koliduje se složkou.');$parent=dirname($target);while(!is_dir($parent)&&$parent!==dirname($parent))$parent=dirname($parent);if(!is_writable($parent))throw new RuntimeException('Programové soubory nejsou zapisovatelné. Upravte oprávnění hostingu před aktualizací.');}

  foreach($targets as $p){$target=upSafeTarget($p);if(is_file($target)){$existing[]=$p;upAtomic($backup.'/'.$p,(string)file_get_contents($target));}}
  upAtomic($backup.'/files.json',json_encode(['files'=>$targets,'existing'=>$existing,'from'=>upVersion(),'to'=>$m['version']],JSON_THROW_ON_ERROR));
  upAtomic($dir.'/maintenance.json',json_encode(['backup'=>basename($backup),'version'=>$m['version'],'started'=>gmdate(DATE_ATOM)],JSON_THROW_ON_ERROR));$started=true;
  $z=new ZipArchive();if($z->open($zipPath)!==true)throw new RuntimeException('Balíček nelze otevřít.');
  foreach($m['files'] as $p=>$hash)upAtomic(upSafeTarget($p),$z->getFromName('payload/'.$p),0644);
  upAtomic(upRoot().'/app/VERSION',$m['version']."\n",0644);
  upAtomic($dir.'/last-result.json',json_encode(['ok'=>true,'version'=>$m['version'],'at'=>gmdate(DATE_ATOM),'backup'=>basename($backup)],JSON_THROW_ON_ERROR));
  if(function_exists('opcache_reset'))opcache_reset();unlink($dir.'/maintenance.json');return $m['version'];
 }catch(Throwable $e){if($started){error_log('Update stopped: '.$e->getMessage());upAtomic($dir.'/last-result.json',json_encode(['ok'=>false,'at'=>gmdate(DATE_ATOM),'message'=>'Aktualizace byla přerušena; aplikace zůstává v režimu údržby.'],JSON_THROW_ON_ERROR));throw new RuntimeException('Aktualizace byla přerušena. Aplikace zůstává v údržbě. Postup obnovy je v docs/AKTUALIZACE.md.',0,$e);}throw $e;
 }finally{if($z)$z->close();flock($lock,LOCK_UN);fclose($lock);}
}
function upFetch(string $url,int $limit):string{
 for($i=0;$i<5;$i++){
  $p=parse_url($url);if(!$p||($p['scheme']??'')!=='https'||!in_array($p['host']??'',['raw.githubusercontent.com','github.com','release-assets.githubusercontent.com','objects.githubusercontent.com'],true)||isset($p['user'])||isset($p['pass'])||isset($p['port']))throw new RuntimeException('Nepovolený zdroj aktualizace.');
  $ctx=stream_context_create(['http'=>['timeout'=>15,'follow_location'=>0,'ignore_errors'=>true,'header'=>"User-Agent: StromyUpdater\r\nAccept: application/octet-stream\r\n"],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);$stream=@fopen($url,'rb',false,$ctx);if(!$stream)throw new RuntimeException('Zdroj aktualizací není dostupný. Aplikaci můžete používat dál.');
  $headers=$http_response_header??[];$status=(int)explode(' ',$headers[0]??'HTTP/1.1 0')[1];
  if(in_array($status,[301,302,303,307,308],true)){fclose($stream);$next='';foreach($headers as $h)if(stripos($h,'Location:')===0)$next=trim(substr($h,9));$url=$next;continue;}
  if($status!==200){fclose($stream);throw new RuntimeException('Zdroj aktualizací vrátil chybu.');}
  $bytes=stream_get_contents($stream,$limit+1);$meta=stream_get_meta_data($stream);fclose($stream);if($bytes===false||strlen($bytes)>$limit||!empty($meta['timed_out']))throw new RuntimeException('Stažení není úplné nebo překročilo limit.');return $bytes;
 }throw new RuntimeException('Příliš mnoho přesměrování.');
}
function upCheck(array $cfg):array{
 $repo=(string)($cfg['repository']??'');if(!preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~D',$repo))throw new RuntimeException('Zdroj aktualizací ještě není nastaven.');
 $r=json_decode(upFetch('https://raw.githubusercontent.com/'.$repo.'/main/release.json',65536),true,16,JSON_THROW_ON_ERROR);
 if(!preg_match('/^\d+\.\d+\.\d+$/D',$r['version']??'')||!preg_match('/^[a-f0-9]{64}$/D',$r['sha256']??'')||!str_starts_with($r['url']??'','https://github.com/'.$repo.'/releases/download/'))throw new RuntimeException('Neplatné informace o vydání.');
 $r['checked_at']=time();upAtomic(upDir().'/release-cache.json',json_encode($r,JSON_THROW_ON_ERROR));return $r;
}
