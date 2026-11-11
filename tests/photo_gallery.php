<?php
require dirname(__DIR__).'/map/public/project_tools/photo_gallery.php';
$files=[];$temp=function()use(&$files){$p=tempnam(sys_get_temp_dir(),'gallery-test-');$files[]=$p;return $p;};
try{
 $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aHfoAAAAASUVORK5CYII=');
 $path=$temp();$zip=new ZipArchive();$zip->open($path,ZipArchive::OVERWRITE);
 $zip->addFromString('fotografie/stromy/strom1/foto_1.png',$png);
 $zip->addFromString('fotografie/plochy/plocha2/foto_2.png',$png);
 $zip->close();$zip->open($path);
 $trees=[['id'=>999,'usr_id'=>'1'],['id'=>998,'usr_id'=>'3']];$areas=[['id'=>888,'code'=>'2']];
 $photos=[['object_type'=>'strom','object_id'=>999,'export_status'=>'zahrnuto','export_path'=>'fotografie/stromy/strom1/foto_1.png','note'=>'<script>unsafe</script>'],['object_type'=>'plocha','object_id'=>888,'export_status'=>'zahrnuto','export_path'=>'fotografie/plochy/plocha2/foto_2.png','note'=>'Plocha']];
 exPhotoGalleries($zip,$trees,$areas,$photos,$temp);$zip->close();$zip->open($path);
 foreach(['galerie/strom_1.html','galerie/plocha_2.html'] as $name){
  $html=$zip->getFromName($name);$doc=new DOMDocument();@$doc->loadHTML($html);
  foreach($doc->getElementsByTagName('img') as $img){$src=$img->getAttribute('src');if(!str_starts_with($src,'data:image/png;base64,')||base64_decode(explode(',',$src,2)[1],true)!==$png)throw new Exception('Embedded bytes differ');}
  foreach($doc->getElementsByTagName('a') as $a)if(!str_starts_with($a->getAttribute('href'),'#'))throw new Exception('External gallery dependency');
  if(str_contains($html,'<script>unsafe'))throw new Exception('Caption not escaped');
 }
 if($zip->getFromName('fotografie/stromy/strom1/foto_1.png')!==$png||$trees[1]['fotogalerie']!==null)throw new Exception('Original or absent photo changed');
 if($trees[0]['foto_hlavni']!=='fotografie/stromy/strom1/foto_1.png')throw new Exception('QGIS path changed');
 $zip->close();echo "PASS self-contained tree/area galleries, original bytes, QGIS paths and escaped captions\n";
}finally{foreach($files as $file)@unlink($file);}
