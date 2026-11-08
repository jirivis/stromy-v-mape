<?php
declare(strict_types=1);
function iaFields():array{return ['code'=>['label'=>'Číslo plochy','type'=>'text']]+ptAreaFields()+['species'=>['label'=>'Druhové složení (JSON)','type'=>'json'],'components'=>['label'=>'Charaktery a výměry (JSON)','type'=>'json']];}
function iaCode(mixed $v):string{if(!is_scalar($v))throw new InvalidArgumentException('Neplatné číslo plochy.');$code=areaText((string)$v,40);if(!preg_match('/^[\p{L}\p{N}_. -]{1,40}$/u',$code))throw new InvalidArgumentException('Vyplňte platné číslo plochy.');return $code;}
function iaValues(array $row,?array $old):array{
 $fields=iaFields();$patch=[];foreach($row as $k=>$v){if($k==='geometry'||$k==='code')continue;if(!isset($fields[$k]))throw new InvalidArgumentException('Pole nelze importovat: '.$k);if($v===null||$v==='')continue;
 if($fields[$k]['type']==='json'){$patch[$k]=is_string($v)?json_decode($v,true,32,JSON_THROW_ON_ERROR):$v;if(!is_array($patch[$k]))throw new InvalidArgumentException('Pole '.$k.' musí být JSON seznam.');}
 else{$patch[$k]=imValue($k,$v,$fields[$k],['Type'=>'text']);if($fields[$k]['type']==='select')$patch[$k]=(string)$patch[$k];}
 }
 $previous=$old?json_decode($old['attributes_json'],true,64,JSON_THROW_ON_ERROR):['status'=>'existing'];$attributes=areaAttributes(array_replace($previous,$patch));[$g,$m2]=areaGeometry($row['geometry']??null);
 return ['code'=>iaCode($row['code']??''),'attributes_json'=>ptJson($attributes),'geometry_json'=>ptJson($g),'area_m2'=>$m2];
}
