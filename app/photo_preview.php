<?php
declare(strict_types=1);
// Call only AFTER authorizing access to the original. Cache is never public.
function svmPhotoPreview(string $bytes):?string{
 if(!function_exists('imagecreatefromstring'))return null;
 $info=@getimagesizefromstring($bytes);if(!$info||$info[0]*$info[1]>24000000)return null;
 $root=__DIR__.'/../private/storage/photo_previews';if(!is_dir($root)&&!@mkdir($root,0700,true)&&!is_dir($root))return null;
 $path=$root.'/'.hash('sha256','preview-v1:'.$bytes).'.jpg';if(is_file($path))return $path;
 $limit=ini_get('memory_limit');$budget=(int)$limit;if(preg_match('/([KMG])$/i',$limit,$m))$budget*=1024**(stripos('KMG',$m[1])+1);
 if($budget>0&&memory_get_usage(true)+$info[0]*$info[1]*12+32*1024*1024>$budget)return null;
 $image=@imagecreatefromstring($bytes);if(!$image)return null;$tmp=null;$small=null;
 try{
  if(($info['mime']??'')==='image/jpeg'&&function_exists('exif_read_data')){
   $stream=fopen('php://temp','w+b');fwrite($stream,$bytes);rewind($stream);$exif=@exif_read_data($stream);fclose($stream);$o=(int)($exif['Orientation']??1);
   if(in_array($o,[2,5,7],true))imageflip($image,IMG_FLIP_HORIZONTAL);if($o===4)imageflip($image,IMG_FLIP_VERTICAL);
   $angle=match($o){3=>180,5,8=>90,6,7=>-90,default=>0};if($angle){$rot=imagerotate($image,$angle,0xffffff);if($rot)$image=$rot;}
  }
  $ratio=min(1,640/max(imagesx($image),imagesy($image)));$w=max(1,(int)round(imagesx($image)*$ratio));$h=max(1,(int)round(imagesy($image)*$ratio));
  $small=imagecreatetruecolor($w,$h);imagefill($small,0,0,imagecolorallocate($small,255,255,255));imagecopyresampled($small,$image,0,0,0,0,$w,$h,imagesx($image),imagesy($image));
  $tmp=tempnam($root,'.preview_');if(!$tmp||!imagejpeg($small,$tmp,82))return null;chmod($tmp,0600);if(!rename($tmp,$path))return null;return $path;
 }finally{if($tmp&&is_file($tmp))@unlink($tmp);unset($small,$image);}
}
