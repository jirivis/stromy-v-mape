<?php
require dirname(__DIR__).'/map/public/project_tools/photo_caption.php';
require dirname(__DIR__).'/map/public/project_tools/photo_gallery.php';
$files=[];$temp=function()use(&$files){$p=tempnam(sys_get_temp_dir(),'caption-test-');$files[]=$p;return $p;};
try{
 $source=$temp();$im=imagecreatetruecolor(640,480);imagefill($im,0,0,imagecolorallocate($im,30,100,40));imagejpeg($im,$source,95);imagedestroy($im);$before=hash_file('sha256',$source);
 if(exCaptionPhoto($source,'  ',$temp)!==$source)throw new Exception('Empty caption must preserve bytes');
 $result=exCaptionPhoto($source,"Žluťoučký kůň – přírůst v koruně\n".str_repeat('Dlouhý popisek fotografie ',12),$temp);
 $size=getimagesize($result);if($size[0]!==640||$size[1]<=480||$size['mime']!=='image/jpeg')throw new Exception('Caption dimensions');
 if(hash_file('sha256',$source)!==$before)throw new Exception('Source changed');
 $im=imagecreatefromjpeg($result);$rgb=imagecolorsforindex($im,imagecolorat($im,0,490));if($rgb['red']<240||$rgb['green']<240||$rgb['blue']<240)throw new Exception('White band');imagedestroy($im);
 // Add a real EXIF orientation tag; the export copy must be portrait.
 $raw=file_get_contents($source);$tiff="II\x2a\x00".pack('V',8).pack('v',1).pack('vvVv',0x112,3,1,6)."\x00\x00".pack('V',0);$exif="Exif\x00\x00".$tiff;
 $rotated=$temp();file_put_contents($rotated,substr($raw,0,2)."\xff\xe1".pack('n',strlen($exif)+2).$exif.substr($raw,2));
 $result=exCaptionPhoto($rotated,'Orientace',$temp);$size=getimagesize($result);if($size[0]!==480||$size[1]<=640)throw new Exception('EXIF orientation');
 $trees=[['id'=>9,'usr_id'=>'1']];$areas=[['id'=>8,'code'=>'2']];$photos=[['object_type'=>'strom','object_id'=>9,'export_status'=>'zahrnuto','export_path'=>'fotografie/stromy/strom1/foto_1.jpg','note'=>'Popisek'],['object_type'=>'plocha','object_id'=>8,'export_status'=>'zahrnuto','export_path'=>'fotografie/plochy/plocha2/foto_2.jpg','note'=>'Plocha']];
 exPhotoReferences($trees,$areas,$photos);if($trees[0]['foto_hlavni']!==$photos[0]['export_path']||$areas[0]['foto_hlavni']!==$photos[1]['export_path']||isset($trees[0]['fotogalerie'])||$photos[0]['note']!=='Popisek')throw new Exception('QGIS references');
 echo "PASS caption copies, wrapping, Czech text, white band, EXIF rotation, unchanged originals and QGIS photo references\n";
}finally{foreach($files as $file)@unlink($file);}
