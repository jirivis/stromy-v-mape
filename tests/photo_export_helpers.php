<?php
require dirname(__DIR__).'/map/public/project_tools/photo_export_helpers.php';
function check($v){if(!$v)throw new RuntimeException('photo export assertion');}
check(exCaptureStamp(null,'20261112_153341.jpg')==='20261112_153341');
check(exCaptureStamp(null,'20261312_153341.jpg')===null);check(exCaptureStamp(null,'random.jpg')===null);
$used=[];$a=exPhotoDestination('strom','66',null,'20261112_153341.jpg','jpg',true,false,$used);$b=exPhotoDestination('strom','66',null,'20261112_153341.jpg','jpg',true,false,$used);
check($a==='fotografie/strom66_20261112_153341_01.jpg'&&$b!==$a);
$c=exPhotoDestination('plocha','66',null,'random.jpg','jpg',true,false,$used);check($c==='fotografie/plocha66_bez_data_01.jpg');
check(!str_contains(exPhotoDestination('strom','../66',null,'random.jpg','jpg',false,false,$used),'../'));
echo "PASS capture naming, missing-date disclosure, flat folder uniqueness and traversal-safe names\n";

$source=tempnam(sys_get_temp_dir(),'stamp_');
try{$im=imagecreatetruecolor(10,10);imagejpeg($im,$source);$raw=file_get_contents($source);
$tiff="II\x2a\x00".pack('V',8).pack('v',1).pack('vvVV',0x8769,4,1,26).pack('V',0).pack('v',1).pack('vvVV',0x9003,2,20,44).pack('V',0)."2026:11:12 15:33:41\0";$exif="Exif\0\0".$tiff;
file_put_contents($source,substr($raw,0,2)."\xff\xe1".pack('n',strlen($exif)+2).$exif.substr($raw,2));
check(exCaptureStamp($source,'20250101_010101.jpg')==='20261112_153341');
echo "PASS actual EXIF capture timestamp takes priority over filename\n";
}finally{unlink($source);}

check(exPhotoCsvCell('=1+1')==="'=1+1");check(exPhotoCsvCell("'literal")==="''literal");check(exPhotoCsvCell('Koruna')==='Koruna');
