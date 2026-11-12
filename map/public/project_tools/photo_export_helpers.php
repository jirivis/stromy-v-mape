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
function exSendZip(string $path,string $name):void{
 // A buffered readfile can consume hundreds of MB before the browser receives it.
 while(ob_get_level()>0){if(!@ob_end_clean())throw new RuntimeException('Hosting nepovolil vypnout výstupní vyrovnávací paměť.');}
 @ini_set('zlib.output_compression','0');if(function_exists('apache_setenv'))@apache_setenv('no-gzip','1');
 $handle=fopen($path,'rb');if($handle===false)throw new RuntimeException('ZIP nelze otevřít.');
 header('Content-Type: application/zip');header('Content-Encoding: identity');header('Cache-Control: private, no-store, no-transform');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.filesize($path));
 try{while(!feof($handle)){if(connection_aborted())break;$chunk=fread($handle,1024*1024);if($chunk===false)throw new RuntimeException('Čtení ZIPu selhalo.');echo $chunk;flush();}}finally{fclose($handle);}
}

// Prefix formula-looking cells and literal apostrophes; the importer reverses this convention.
function exPhotoCsvCell(mixed $value):string{$v=(string)$value;return preg_match('/^(?:[\s]*[=+@-]|[\x09\x0d\x0a\x27])/u',$v)?"'".$v:$v;}
