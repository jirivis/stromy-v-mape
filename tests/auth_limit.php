<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/auth_limit.php';
function expect(bool $v):void{if(!$v)throw new RuntimeException('Rate limit assertion failed');}
$dir=sys_get_temp_dir().'/svm-limit-'.bin2hex(random_bytes(8));mkdir($dir,0700);$file=$dir.'/limits.sqlite3';
try{
 $_SERVER['REMOTE_ADDR']='192.0.2.1';
 for($i=0;$i<8;$i++)expect(svmAuthLimit('login','user:1',1000,$file)===0);
 expect(svmAuthLimit('login','user:1',1000,$file)===60);
 // Other accounts sharing a proxy are not blocked.
 expect(svmAuthLimit('login','user:2',1000,$file)===0);
 // Changing browser/IP does not bypass the account-wide quota.
 $_SERVER['REMOTE_ADDR']='192.0.2.2';for($i=0;$i<8;$i++)expect(svmAuthLimit('login','user:1',1000,$file)===0);
 $_SERVER['REMOTE_ADDR']='192.0.2.3';for($i=0;$i<4;$i++)expect(svmAuthLimit('login','user:1',1000,$file)===0);
 expect(svmAuthLimit('login','user:1',1000,$file)===900);
 expect(svmAuthLimit('login','user:1',1900,$file)===0);
 for($i=0;$i<3;$i++)expect(svmAuthLimit('reset','user:1',2000,$file)===0);
 expect(svmAuthLimit('reset','user:1',2000,$file)===3600);
 expect(svmAuthLimit('reset','user:1',5600,$file)===0);
 $jobs=[];
 for($i=0;$i<12;$i++){
  $code='require '.var_export(dirname(__DIR__).'/app/auth_limit.php',true).'; $_SERVER["REMOTE_ADDR"]="192.0.2.9"; echo svmAuthLimit("login","parallel",6000,'.var_export($file,true).');';
  $proc=proc_open([PHP_BINARY,'-r',$code],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$jobs[]=[$proc,$pipes];
 }
 $allowed=0;
 foreach($jobs as [$proc,$pipes]){$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);expect(proc_close($proc)===0);expect($err==='');if($out==='0')$allowed++;else expect($out==='60');}
 expect($allowed===8);
 echo "PASS persistent limiter: burst/account quotas, proxy separation, expiry and reset cooldown\n";
}finally{foreach(glob($dir.'/*') as $p)unlink($p);rmdir($dir);}
