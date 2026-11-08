<?php
declare(strict_types=1);
function imFields():array {
 $f=ptTreeFields();foreach(['lat_wgs84','lng_wgs84','x_sjtsk','y_sjtsk'] as $k)$f[$k]=['label'=>$k,'type'=>'number'];if(function_exists('ptTableFields')){$ordered=[];foreach(ptTableFields('trees',[]) as $k=>$meta)if(isset($f[$k])){$ordered[$k]=$f[$k];$ordered[$k]['label']=$meta['label'];}return $ordered+$f;}return $f;
}
function imValue(string $key,mixed $raw,array $field,array $column):mixed {
 if(!is_scalar($raw)&&$raw!==null)throw new InvalidArgumentException('Neplatná hodnota '.$key);
 $v=trim((string)$raw);if(mb_strlen($v)>5000)throw new InvalidArgumentException('Příliš dlouhý text '.$key);
 if($v==='')return null;
 if(($field['type']??'')==='number'){
  $v=str_replace(',','.',$v);if(!is_numeric($v)||!is_finite((float)$v))throw new InvalidArgumentException('Neplatné číslo '.$key);$v=(float)$v;
  if(!in_array($key,['lat_wgs84','lng_wgs84','x_sjtsk','y_sjtsk'],true)&&$v<0)throw new InvalidArgumentException('Záporná hodnota '.$key);
  if(in_array($key,['usr_id','age'],true)&&floor($v)!=$v)throw new InvalidArgumentException('Je potřeba celé číslo '.$key);
 }elseif(($field['type']??'')==='date'){
  $original=$v;
  if(preg_match('/^(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})$/D',$v,$m))$v=sprintf('%04d-%02d-%02d',$m[3],$m[2],$m[1]);
  elseif(preg_match('/^(\d{4})([-\/])(\d{1,2})\2(\d{1,2})$/D',$v,$m))$v=sprintf('%04d-%02d-%02d',$m[1],$m[3],$m[4]);
  try{$v=areaDate($v);}catch(InvalidArgumentException $e){throw new InvalidArgumentException('Neplatné datum v poli '.($field['label']??$key).' ('.$key.'): „'.mb_substr($original,0,100).'“. Použijte RRRR-MM-DD, RRRR/MM/DD nebo D. M. RRRR.');}
 }elseif(($field['type']??'')==='select'){
  $options=$field['options']??[];if($options&&!array_key_exists($v,$options)){$hits=[];foreach($options as $option=>$label)if(mb_strtolower(trim((string)$option),'UTF-8')===mb_strtolower($v,'UTF-8')||mb_strtolower(trim((string)$label),'UTF-8')===mb_strtolower($v,'UTF-8'))$hits[]=$option;if(count($hits)!==1)throw new InvalidArgumentException('Neplatná volba '.$key.': '.$v);$v=$hits[0];}
 }
 if(preg_match('/^(?:var)?char\((\d+)\)/i',$column['Type'],$m)&&mb_strlen((string)$v)>(int)$m[1])throw new InvalidArgumentException('Text je příliš dlouhý '.$key);
 if(is_numeric($v)&&preg_match('/^(?:dbh[1-4]_cm|girth[1-4]_cm|height|canopy_h_m|spread_m)$/D',$key)){
  if(preg_match('/^(tinyint|smallint|mediumint|int|bigint)/i',$column['Type']))$v=round((float)$v,0,PHP_ROUND_HALF_UP);
  elseif(preg_match('/^(?:decimal|numeric)\(\d+,\s*(\d+)\)/i',$column['Type'],$m))$v=round((float)$v,(int)$m[1],PHP_ROUND_HALF_UP);
 }
 if(preg_match('/^(tinyint|smallint|mediumint|int|bigint)/i',$column['Type'])&&is_numeric($v)&&floor((float)$v)!=(float)$v)throw new InvalidArgumentException('Databáze vyžaduje celé číslo '.$key);
 return $v;
}
function imPatch(array $row,array $schema,array $fields):array {
 $out=[];foreach($row as $k=>$v){if(!isset($fields[$k],$schema[$k]))throw new InvalidArgumentException('Pole nelze importovat: '.$k);if($v===null||trim((string)$v)==='')continue;$out[$k]=imValue($k,$v,$fields[$k],$schema[$k]);}
 $number=$out['usr_id']??null;if(!is_numeric($number)||$number<1||$number>2147483647||floor((float)$number)!=$number)throw new InvalidArgumentException('Číslo stromu (usr_id) musí být kladné celé číslo.');
 $lat=$out['lat_wgs84']??null;$lon=$out['lng_wgs84']??null;
 if(($lat===null)!==($lon===null))throw new InvalidArgumentException('Poloha vyžaduje šířku i délku.');
 if($lat!==null&&(abs($lat)>90||abs($lon)>180||($lat==0&&$lon==0)))throw new InvalidArgumentException('Poloha je mimo rozsah.');
 if((isset($out['x_sjtsk'])||isset($out['y_sjtsk']))&&($lat===null||!isset($out['x_sjtsk'],$out['y_sjtsk'])))throw new InvalidArgumentException('Neúplné souřadnice S-JTSK.');
 // Never retain old JTSK coordinates after updating only WGS84.
 if($lat!==null&&!isset($out['x_sjtsk'],$out['y_sjtsk']))foreach(['x_sjtsk','y_sjtsk'] as $k)if(isset($schema[$k]))$out[$k]=$schema[$k]['Null']==='YES'?null:'';
 return $out;
}
function imCreateValues(array $patch,array $schema,array $fields,int $project):array {
 $out=['project_id'=>$project]+$patch;
 foreach($schema as $key=>$col){if(array_key_exists($key,$out)||$col['Default']!==null||$col['Null']==='YES'||str_contains(strtolower($col['Extra']??''),'auto_increment')||str_contains(strtolower($col['Extra']??''),'generated'))continue;
  if(isset($fields[$key])&&preg_match('/^(?:var)?char|text/i',$col['Type']))$out[$key]='';
  else throw new InvalidArgumentException('Pro nový strom chybí povinné databázové pole '.$key.'. Doplňte je do importu.');
 }return $out;
}
