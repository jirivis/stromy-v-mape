<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
// Run: php tests/update_file_only.php (OpenSSL, ZIP, mysqli required).
function check(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);}
function removeTree(string $path):void {
 foreach(scandir($path) as $name){if($name==='.'||$name==='..')continue;$p=$path.'/'.$name;if(is_dir($p))removeTree($p);else unlink($p);}rmdir($path);
}
$root=sys_get_temp_dir().'/svm-update-test-'.bin2hex(random_bytes(6));
mkdir($root.'/app',0700,true);mkdir($root.'/private',0700);
copy(dirname(__DIR__).'/app/update.php',$root.'/app/update.php');
copy(dirname(__DIR__).'/app/update_database.php',$root.'/app/update_database.php');
require $root.'/app/update.php';
try {
 file_put_contents($root.'/app/VERSION',"2.25.1\n");
 $base=['repository'=>'owner/old','public_key'=>'trusted','check_on_dashboard'=>false];
 upSaveSource('owner/releases',true);$cfg=upConfig($base);
 check($cfg['repository']==='owner/releases'&&$cfg['check_on_dashboard']&&$cfg['public_key']==='trusted','Source settings preserve trust key');
 try{upSaveSource('https://evil.example/repo',true);throw new LogicException('Invalid source accepted');}catch(RuntimeException $e){}
 $entry=fn($from,$version)=>['from'=>$from,'version'=>$version,'url'=>'https://github.com/owner/releases/releases/download/v'.$version.'/update.zip','sha256'=>str_repeat('a',64),'notes'=>'test'];
 $index=['packages'=>[$entry('2.25.0','2.25.1'),$entry('2.25.1','2.25.2'),$entry('2.25.2','2.26.0')]];
 check(upSelectRelease($index,'owner/releases','2.25.1')['version']==='2.25.2','Select compatible update instead of newest incompatible');
 check(upSelectRelease($index,'owner/releases','2.26.0')['available']===false,'No downgrade or missing path offered');
 $bad=$entry('2.25.1','2.25.2');$bad['url']='https://evil.example/update.zip';
 try{upSelectRelease($bad,'owner/releases','2.25.1');throw new LogicException('Foreign asset accepted');}catch(RuntimeException $e){}
 $cached=$entry('2.25.1','2.25.2')+['repository'=>'owner/releases','installed_version'=>'2.25.1','checked_at'=>time()];
 upAtomic(upDir().'/release-cache.json',json_encode($cached));
 check(upCachedRelease($cfg)!==null,'Matching cache accepted');
 check(upCachedRelease($base)===null,'Foreign repository cache rejected');
 file_put_contents($root.'/app/VERSION',"2.25.2\n");check(upCachedRelease($cfg)===null,'Old installed-version cache rejected');
 upSaveSource('',false);check(!is_file(upDir().'/release-cache.json'),'Changing source clears cache');
 check(upPath("map/public/uploads/.htaccess")&&upPath("map/public/photos/.htaccess"),"Allow signed storage protection");
 foreach(["map/public/uploads/a.jpg","map/public/photos/a.php","map/public/uploads/sub/.htaccess","private/config.php","map/public/uploads/../a.php"] as $path)check(!upPath($path),"Protected storage path: ".$path);
 $key=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
 check($key!==false,'Test signing key');$public=openssl_pkey_get_details($key)['key'];
 // Deliberately disconnected: any query would fail, including CREATE IF NOT EXISTS.
 $db=new mysqli();
 $make=function(string $zip,array $migrations)use($key):void{
  $files=['example.txt'=>'new content'];foreach($migrations as $p)$files[$p]='ALTER TABLE example ADD example INT;';
  $manifest=['product'=>'stromy-v-mape','from'=>'2.23.0','version'=>'2.23.1','php_min'=>'8.2','files'=>array_map(fn($b)=>hash('sha256',$b),$files),'migrations'=>$migrations];
  $raw=json_encode($manifest,JSON_THROW_ON_ERROR);openssl_sign($raw,$sig,$key,OPENSSL_ALGO_SHA256);
  $z=new ZipArchive();check($z->open($zip,ZipArchive::CREATE)===true,'Create package');$z->addFromString('manifest.json',$raw);$z->addFromString('signature.txt',base64_encode($sig));foreach($files as $p=>$b)$z->addFromString('payload/'.$p,$b);$z->close();
 };
 file_put_contents($root.'/app/VERSION',"2.23.0\n");file_put_contents($root.'/example.txt','original');
 $make($root.'/migration.zip',['database/migrations/change.sql']);
 try{upApply($root.'/migration.zip',$public,$db);throw new LogicException('Migration was accepted');}catch(RuntimeException $e){check(str_contains($e->getMessage(),'ověřitelný postup'),'Migration rejected clearly');}
 check(file_get_contents($root.'/example.txt')==='original','Original file retained');
 check(upVersion()==='2.23.0','Version retained');
 check(!file_exists(upDir().'/maintenance.json'),'No maintenance on rejection');
 check(glob(upDir().'/backup-*')===[],'No backup mutation on rejection');
 $make($root.'/files.zip',[]);
 check(upApply($root.'/files.zip',$public,$db)==='2.23.1','File-only update completes without database');
 check(file_get_contents($root.'/example.txt')==='new content','Payload installed');
 check(!file_exists(upDir().'/maintenance.json'),'Maintenance cleared');
 $backups=glob(upDir().'/backup-*');check(count($backups)===1,'Backup created');check(file_get_contents($backups[0].'/example.txt')==='original','Backup preserves original');
 echo "PASS: signed SQL package rejected before changes; signed file-only package succeeds without database; backup and maintenance verified.\n";
}finally{removeTree($root);}
