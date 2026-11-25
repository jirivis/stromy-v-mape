<?php
require_once __DIR__.'/../map/public/areas/validation.php';
$a=areaAttributes(['species'=>[['scientific_name'=>'Ribes alpinum','author'=>'L.','share'=>100]]]);
if($a['species'][0]['author']!=='L.')throw new RuntimeException('Author lost');
$a=areaAttributes(['species'=>[['scientific_name'=>'Ribes alpinum']]]);
if($a['species'][0]['author']!=='')throw new RuntimeException('Legacy species rejected');
try{areaAttributes(['species'=>[['scientific_name'=>'Ribes','author'=>str_repeat('x',251)]]]);throw new RuntimeException('Invalid author accepted');}catch(InvalidArgumentException $e){}
echo "PASS area authors validation and legacy records\n";
