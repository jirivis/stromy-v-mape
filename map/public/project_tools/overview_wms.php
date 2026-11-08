<?php
require_once __DIR__.'/common.php';
try {
 $sources=['ortho'=>['https://ags.cuzk.gov.cz/arcgis1/services/ORTOFOTO/MapServer/WMSServer','0'],'ztm'=>['https://ags.cuzk.gov.cz/arcgis1/services/ZTM/ZTM10/MapServer/WMSServer','0'],'cadastre'=>['https://services.cuzk.gov.cz/wms/local-KM-wms.asp','KN']];
 $source=$sources[$_GET['layer']??'']??null;if(!$source)throw new InvalidArgumentException('Neznámý podklad.');$bbox=explode(',',$_GET['bbox']??'');if(count($bbox)!==4)throw new InvalidArgumentException('Neplatný rozsah.');foreach($bbox as &$v){if(!is_numeric($v)||!is_finite((float)$v)||abs((float)$v)>20037509)throw new InvalidArgumentException('Neplatná souřadnice.');$v=(float)$v;}unset($v);if($bbox[0]>=$bbox[2]||$bbox[1]>=$bbox[3])throw new InvalidArgumentException('Prázdný rozsah.');
 $url=$source[0].'?'.http_build_query(['SERVICE'=>'WMS','VERSION'=>'1.3.0','REQUEST'=>'GetMap','LAYERS'=>$source[1],'STYLES'=>'','CRS'=>'EPSG:3857','BBOX'=>implode(',',$bbox),'WIDTH'=>1600,'HEIGHT'=>950,'FORMAT'=>'image/png','TRANSPARENT'=>'TRUE']);
 // Fixed public providers only; no caller supplied URL, redirects or credentials.
 $bytes='';if(function_exists('curl_init')){$c=curl_init($url);curl_setopt_array($c,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>35,CURLOPT_WRITEFUNCTION=>function($c,$chunk)use(&$bytes){if(strlen($bytes)+strlen($chunk)>16000000)return 0;$bytes.=$chunk;return strlen($chunk);}]);$ok=curl_exec($c);$status=curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);if(!$ok||$status!==200)throw new RuntimeException('WMS neodpovídá.');}
 else {$bytes=@file_get_contents($url,false,stream_context_create(['http'=>['timeout'=>35,'follow_location'=>0]]),0,16000001);if($bytes===false||strlen($bytes)>16000000)throw new RuntimeException('WMS není dostupné; hosting potřebuje cURL nebo allow_url_fopen.');}
 if(!str_starts_with($bytes,"\x89PNG\r\n\x1a\n"))throw new RuntimeException('Podklad nevrátil obrázek.');header('Content-Type: image/png');echo $bytes;
}catch(Throwable $e){http_response_code(422);header('Content-Type: text/plain; charset=utf-8');echo $e->getMessage();}
