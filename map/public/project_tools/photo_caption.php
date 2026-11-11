<?php
// Creates only an export copy. Never writes to the source photograph.
function exCaptionPhoto(string $source,string $note,callable $temp):string{
 if(trim($note)==='')return $source;
 $font=dirname(__DIR__,3).'/assets/fonts/DejaVuSans-Export.ttf';
 if(!function_exists('imagettftext')||!is_file($font))throw new RuntimeException('Popisky fotek vyžadují PHP GD s FreeType a exportní font.');
 if(mb_strlen($note)>10000)throw new RuntimeException('Popisek fotografie je příliš dlouhý (maximum 10 000 znaků).');
 $info=@getimagesize($source);
 if(!$info||$info[0]*$info[1]>24000000)throw new RuntimeException('Fotografie pro vepsání popisku musí mít nejvýše 24 megapixelů.');
 if(($info['mime']??'')==='image/jpeg'&&!function_exists('exif_read_data'))throw new RuntimeException('Popisky JPEG fotografií vyžadují PHP EXIF pro zachování orientace.');
 $limit=ini_get('memory_limit');$bytes=(int)$limit;
 if(preg_match('/([KMG])$/i',$limit,$m))$bytes*=1024**(stripos('KMG',$m[1])+1);
 if($bytes>0&&memory_get_usage(true)+$info[0]*$info[1]*12+32*1024*1024>$bytes)throw new RuntimeException('Hosting nemá dostatek paměti pro popisek této fotografie. Exportujte bez vepsaných popisků.');
 $raw=file_get_contents($source);$image=@imagecreatefromstring($raw);unset($raw);
 if(!$image)throw new RuntimeException('Fotografii nelze zpracovat pro popisek.');
 $canvas=null;
 try{
  $orientation=1;
  if(($info['mime']??'')==='image/jpeg'){$exif=@exif_read_data($source);$orientation=(int)($exif['Orientation']??1);}
  if(in_array($orientation,[2,5,7],true))imageflip($image,IMG_FLIP_HORIZONTAL);
  if($orientation===4)imageflip($image,IMG_FLIP_VERTICAL);
  $angle=match($orientation){3=>180,5,8=>90,6,7=>-90,default=>0};
  if($angle){$rotated=imagerotate($image,$angle,0xffffff);imagedestroy($image);$image=$rotated;}
  $w=imagesx($image);$h=imagesy($image);$size=max(10,(int)round($w/75));$pad=max(8,(int)round($w/80));$lineHeight=(int)ceil($size*1.7);$available=max(1,$w-2*$pad);
  $width=function($text)use($font,$size){$b=imagettfbbox($size,0,$font,$text);return max($b[0],$b[2],$b[4],$b[6])-min($b[0],$b[2],$b[4],$b[6]);};
  $lines=[];
  foreach(preg_split('/\R/u',trim($note)) as $paragraph){
   $line='';foreach(preg_split('/\s+/u',trim($paragraph)) as $word){
    $candidate=$line===''?$word:$line.' '.$word;
    if($width($candidate)<=$available){$line=$candidate;continue;}
    if($line!==''){$lines[]=$line;$line='';}
    foreach(preg_split('//u',$word,-1,PREG_SPLIT_NO_EMPTY) as $char){if($line!==''&&$width($line.$char)>$available){$lines[]=$line;$line='';}$line.=$char;}
   }$lines[]=$line;
  }
  $height=$h+2*$pad+count($lines)*$lineHeight;
  if(count($lines)>100||$w*$height>30000000)throw new RuntimeException('Popisek fotografie je příliš dlouhý pro tento snímek.');
  $canvas=imagecreatetruecolor($w,$height);$white=imagecolorallocate($canvas,255,255,255);$black=imagecolorallocate($canvas,25,25,25);imagefill($canvas,0,0,$white);imagecopy($canvas,$image,0,0,0,0,$w,$h);
  foreach($lines as $i=>$line)imagettftext($canvas,$size,0,$pad,(int)round($h+$pad+($i+1)*$lineHeight-$size/3),$black,$font,$line);
  $output=$temp();if(!imagejpeg($canvas,$output,95))throw new RuntimeException('Nelze uložit fotografii s popiskem.');return $output;
 }finally{if($canvas)imagedestroy($canvas);imagedestroy($image);}
}
