<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
// Persistent across sessions; no forwarded headers are trusted.
function svmAuthLimit(string $action,string $subject,?int $now=null,?string $storage=null):int{
 $now??=time();$storage??=dirname(__DIR__).'/private/auth_limits.sqlite3';
 $rules=$action==='login'?[["account",20,900],["client",8,60]]:[["account",3,3600]];
 $db=new SQLite3($storage);@chmod($storage,0600);$db->enableExceptions(true);$db->busyTimeout(3000);
 try{
  $db->exec('CREATE TABLE IF NOT EXISTS secret (id INTEGER PRIMARY KEY CHECK(id=1), value TEXT NOT NULL)');
  $db->exec('CREATE TABLE IF NOT EXISTS limits (key TEXT PRIMARY KEY, hits INTEGER NOT NULL, expires INTEGER NOT NULL)');
  $db->exec('CREATE INDEX IF NOT EXISTS limits_expiry ON limits(expires)');
  $db->exec('BEGIN IMMEDIATE');
  $s=$db->prepare('INSERT OR IGNORE INTO secret(id,value) VALUES(1,:value)');$s->bindValue(':value',bin2hex(random_bytes(32)));$s->execute();
  $secret=(string)$db->querySingle('SELECT value FROM secret WHERE id=1');
  $s=$db->prepare('DELETE FROM limits WHERE expires<=:now');$s->bindValue(':now',$now,SQLITE3_INTEGER);$s->execute();
  $buckets=[];$retry=0;
  foreach($rules as [$scope,$max,$window]){
   $key=hash_hmac('sha256',$action."\0".$scope."\0".$subject.($scope==='client'?"\0".($_SERVER['REMOTE_ADDR']??'unknown'):''),$secret);
   $s=$db->prepare('SELECT hits,expires FROM limits WHERE key=:key');$s->bindValue(':key',$key);$row=$s->execute()->fetchArray(SQLITE3_ASSOC);
   if($row&&$row['hits']>=$max)$retry=max($retry,(int)$row['expires']-$now);
   $buckets[]=[$key,$row,$window];
  }
  if($retry>0){$db->exec('COMMIT');return $retry;}
  if((int)$db->querySingle('SELECT COUNT(*) FROM limits')+count($buckets)>10000)throw new RuntimeException('Authentication limiter capacity');
  foreach($buckets as [$key,$row,$window]){
   $s=$db->prepare('INSERT INTO limits(key,hits,expires) VALUES(:key,1,:expires) ON CONFLICT(key) DO UPDATE SET hits=hits+1');
   $s->bindValue(':key',$key);$s->bindValue(':expires',$now+$window,SQLITE3_INTEGER);$s->execute();
  }
  $db->exec('COMMIT');return 0;
 }catch(Throwable $e){try{$db->exec('ROLLBACK');}catch(Throwable $ignored){}throw $e;}finally{$db->close();}
}
function svmEnforceAuthLimit(string $action,string $subject):void{
 try{$retry=svmAuthLimit($action,$subject);}catch(Throwable $e){error_log('Authentication limiter unavailable: '.get_class($e));http_response_code(503);header('Retry-After: 60');exit('Přihlášení nebo obnova hesla nyní nejsou dostupné. Zkuste to později.');}
 if($retry>0){http_response_code(429);header('Retry-After: '.$retry);exit('Příliš mnoho pokusů. Zkuste to znovu za '.(int)ceil($retry/60).' min.');}
}
