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
