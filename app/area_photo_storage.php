<?php
declare(strict_types=1);
function apsRoot():string{$root=dirname(__DIR__).'/private/storage/area_photos';if(!is_dir($root)&&!mkdir($root,0700,true)&&!is_dir($root))throw new RuntimeException('Nelze vytvořit úložiště fotografií.');$real=realpath($root);if($real===false)throw new RuntimeException('Úložiště není dostupné.');return $real;}
function apsPut(string $bytes,string $mime):string{
 $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;if(!$ext||strlen($bytes)>10*1024*1024||$bytes==='')throw new RuntimeException('Neplatná fotografie.');
 $hash=hash('sha256',$bytes);$key=$hash.'.'.$ext;$path=apsRoot().'/'.$key;
 if(is_file($path)){if(!hash_equals($hash,(string)hash_file('sha256',$path)))throw new RuntimeException('Kontrola uložené fotografie selhala.');return $key;}
 $tmp=tempnam(apsRoot(),'.photo_');if($tmp===false)throw new RuntimeException('Nelze připravit fotografii.');
 try{if(file_put_contents($tmp,$bytes,LOCK_EX)!==strlen($bytes))throw new RuntimeException('Fotografie se nezapsala celá.');chmod($tmp,0600);if(!hash_equals($hash,(string)hash_file('sha256',$tmp)))throw new RuntimeException('Kontrola fotografie selhala.');if(!rename($tmp,$path))throw new RuntimeException('Fotografii nelze dokončit.');return $key;}finally{if(is_file($tmp))unlink($tmp);}
}
function apsRead(array $photo):string{
 $key=(string)($photo['file_path']??'');if($key!==''){
  if(!preg_match('/^([a-f0-9]{64})\.(jpg|png|webp)$/D',$key,$m))throw new RuntimeException('Neplatná cesta fotografie.');
  $root=apsRoot();$path=realpath($root.'/'.$key);if($path&&str_starts_with($path,$root.DIRECTORY_SEPARATOR)&&is_file($path)&&filesize($path)<=10*1024*1024){$bytes=file_get_contents($path);if($bytes!==false&&hash_equals($m[1],hash('sha256',$bytes)))return $bytes;}
  // During migration retain access to the original verified database copy.
 }
 if(isset($photo['content'])&&is_string($photo['content'])&&$photo['content']!=='')return $photo['content'];
 throw new RuntimeException('Fotografie není dostupná nebo je poškozená.');
}
