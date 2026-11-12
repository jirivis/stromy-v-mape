<?php
declare(strict_types=1);
// Never substitute upload time or filesystem mtime for the actual capture date.
function exCaptureStamp(?string $path,string $name):?string{
 if($path&&function_exists('exif_read_data')){
  $exif=@exif_read_data($path);$value=$exif['DateTimeOriginal']??$exif['DateTimeDigitized']??null;
  if(is_string($value)&&preg_match('/^(\d{4}):(\d{2}):(\d{2}) (\d{2}):(\d{2}):(\d{2})$/D',$value,$m)&&checkdate((int)$m[2],(int)$m[3],(int)$m[1])&&(int)$m[4]<24&&(int)$m[5]<60&&(int)$m[6]<60)return $m[1].$m[2].$m[3].'_'.$m[4].$m[5].$m[6];
 }
 if(preg_match('/(?<!\d)(20\d{2})(\d{2})(\d{2})[_-]?(\d{2})(\d{2})(\d{2})(?!\d)/',$name,$m)&&checkdate((int)$m[2],(int)$m[3],(int)$m[1])&&(int)$m[4]<24&&(int)$m[5]<60&&(int)$m[6]<60)return $m[1].$m[2].$m[3].'_'.$m[4].$m[5].$m[6];
 return null;
}
function exPhotoDestination(string $kind,string $code,?string $source,string $original,string $extension,bool $flat,bool $archived,array &$used):string{
 $safe=rawurlencode($code);$stamp=exCaptureStamp($source,$original)??'bez_data';
 $folder=$flat?'fotografie/':'fotografie/'.($kind==='strom'?'stromy/':'plochy/').$kind.$safe.'/'.($archived?'archivovane/':'');
 $base=$folder.$kind.$safe.'_'.$stamp.($archived?'_archiv':'');$n=1;
 do{$path=$base.'_'.str_pad((string)$n++,2,'0',STR_PAD_LEFT).'.'.$extension;}while(isset($used[$path]));$used[$path]=true;return $path;
}
function exSendZip(string $path,string $name,bool $ranges=false):void{
 // A buffered readfile can consume hundreds of MB before the browser receives it.
 while(ob_get_level()>0){if(!@ob_end_clean())throw new RuntimeException('Hosting nepovolil vypnout výstupní vyrovnávací paměť.');}
 @ini_set('zlib.output_compression','0');if(function_exists('apache_setenv'))@apache_setenv('no-gzip','1');
 $handle=fopen($path,'rb');if($handle===false)throw new RuntimeException('ZIP nelze otevřít.');
 // Read the actual open file, never PHP's cached stat of a temporary file.
 $size=fstat($handle)['size'];$start=0;$end=$size-1;
 if($ranges){header('Accept-Ranges: bytes');$range=$_SERVER['HTTP_RANGE']??'';
  if($range!==''){
   if(!preg_match('/^bytes=(\d*)-(\d*)$/D',$range,$m)||($m[1]===''&&$m[2]==='')){fclose($handle);header('Content-Range: bytes */'.$size);http_response_code(416);return;}
   if($m[1]===''){$start=max(0,$size-(int)$m[2]);}else{$start=(int)$m[1];if($m[2]!=='')$end=min($end,(int)$m[2]);}
   if($start>$end||$start>=$size){fclose($handle);header('Content-Range: bytes */'.$size);http_response_code(416);return;}
   http_response_code(206);header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
  }
 }
 header('Content-Type: application/zip');header('Content-Encoding: identity');header('Cache-Control: private, no-store, no-transform');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.($end-$start+1));
 try{if(fseek($handle,$start)!==0)throw new RuntimeException('ZIP nelze číst.');$remaining=$end-$start+1;while($remaining>0){if(connection_aborted())break;$chunk=fread($handle,min($remaining,1024*1024));if($chunk===false||$chunk==='')throw new RuntimeException('Čtení ZIPu selhalo.');$remaining-=strlen($chunk);echo $chunk;flush();}}finally{fclose($handle);}

}

// Prefix formula-looking cells and literal apostrophes; the importer reverses this convention.
function exPhotoCsvCell(mixed $value):string{$v=(string)$value;return preg_match('/^(?:[\s]*[=+@-]|[\x09\x0d\x0a\x27])/u',$v)?"'".$v:$v;}

// Prepared archives remain private and are scoped to both the user and active project.
function exDownloadRoot():string{
 $dir=dirname(__DIR__,3).'/private/storage/exports';
 if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Nelze vytvořit dočasné úložiště exportu.');
 return $dir;
}
function exPrepareDownload(string $path,string $name,int $user,int $project):array{
 $dir=exDownloadRoot();
 foreach(glob($dir.'/*.json')?:[] as $old){if(filemtime($old)<time()-3600){@unlink(substr($old,0,-5).'.zip');@unlink($old);}}
 $token=bin2hex(random_bytes(24));$dest=$dir.'/'.$token.'.zip';
 if(!rename($path,$dest))throw new RuntimeException('Nelze uložit hotový export.');chmod($dest,0600);
 try{
  $meta=['user'=>$user,'project'=>$project,'name'=>$name,'expires'=>time()+3600];
  if(file_put_contents($dir.'/'.$token.'.json',json_encode($meta,JSON_THROW_ON_ERROR),LOCK_EX)===false)throw new RuntimeException('Nelze uložit odkaz exportu.');chmod($dir.'/'.$token.'.json',0600);
 }catch(Throwable $e){@unlink($dest);throw $e;}
 return ['download'=>'export.php?download='.$token,'bytes'=>filesize($dest),'expires_in'=>3600];
}
function exDownload(string $token,int $user,int $project):void{
 if(!preg_match('/^[a-f0-9]{48}$/D',$token)){http_response_code(404);echo 'Export není dostupný.';return;}
 $base=exDownloadRoot().'/'.$token;$meta=is_file($base.'.json')?json_decode(file_get_contents($base.'.json'),true):null;
 if(!$meta||$meta['user']!==$user||$meta['project']!==$project||$meta['expires']<time()||!is_file($base.'.zip')){http_response_code(404);echo 'Export vypršel nebo k němu nemáte přístup. Připravte nový export.';return;}
 session_write_close();@set_time_limit(0);exSendZip($base.'.zip',$meta['name'],true);
}
