<?php
require_once __DIR__.'/common.php';
try {ptCheckPost();$raw=file_get_contents('php://input',false,null,0,200001);if(strlen($raw)>200000)throw new InvalidArgumentException('Příliš velký výběr.');$d=json_decode($raw,true,16,JSON_THROW_ON_ERROR);$selection=$d['selection']??null;
 if($selection!==null)foreach(['trees','areas'] as $kind)if(!isset($selection[$kind])||!is_array($selection[$kind])||count($selection[$kind])>5000)throw new InvalidArgumentException('Neplatný výběr.');$features=[];$missing=0;
 foreach(['trees','areas'] as $kind){$ids=$selection===null?null:array_fill_keys(array_map('strval',$selection[$kind]),true);foreach(ptRows($kind) as $row){if($ids!==null&&!isset($ids[(string)$row['id']]))continue;$g=ptGeometry($kind,$row);if(!$g){$missing++;continue;}if(count($features)>=5000)throw new InvalidArgumentException('Přehledka podporuje nejvýše 5000 objektů. Použijte výběr.');$v=$kind==='trees'?$row:json_decode($row['attributes_json'],true);$p=['kind'=>$kind,'code'=>$row[$kind==='trees'?'usr_id':'code']??''];foreach(['longevity','stability','tree_health','vitality','spread_m','angio_gymno','status'] as $k)$p[$k]=$v[$k]??null;$features[]=['geometry'=>$g,'properties'=>$p];}}
 ptOut(['features'=>$features,'missing'=>$missing,'project_id'=>$project]);
}catch(Throwable $e){ptOut(['error'=>$e instanceof mysqli_sql_exception?'Data nelze načíst.':$e->getMessage()],422);}
