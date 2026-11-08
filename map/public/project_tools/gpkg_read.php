<?php
declare(strict_types=1);
// Read-only GeoPackage reader. No SQL supplied by the caller is executed.
function giIdent(string $s):string{if($s===''||strlen($s)>128||str_contains($s,"\0"))throw new InvalidArgumentException('Neplatný název tabulky nebo sloupce.');return '"'.str_replace('"','""',$s).'"';}
function giTable(SQLite3 $db,string $name):array{
 $q=$db->prepare("SELECT type,sql FROM sqlite_master WHERE name=:name");$q->bindValue(':name',$name,SQLITE3_TEXT);$r=$q->execute()->fetchArray(SQLITE3_ASSOC);
 if(!$r||$r['type']!=='table'||!preg_match('/^\s*CREATE\s+TABLE\b/i',(string)$r['sql']))throw new InvalidArgumentException('Podporovány jsou pouze běžné tabulky GPKG.');
 $info=$db->query('PRAGMA table_xinfo('.giIdent($name).')');$cols=[];while($c=$info->fetchArray(SQLITE3_ASSOC)){if((int)($c['hidden']??0)!==0)throw new InvalidArgumentException('Vypočítané a skryté sloupce nejsou podporované.');$cols[$c['name']]=$c;if(count($cols)>102)throw new InvalidArgumentException('Vrstva má příliš mnoho sloupců.');}return $cols;
}
function giOpen(string $path):SQLite3{
 if(!class_exists('SQLite3'))throw new RuntimeException('Na hostingu chybí PHP SQLite3.');
 if(file_get_contents($path,false,null,0,16)!=="SQLite format 3\0")throw new InvalidArgumentException('Soubor není GeoPackage / SQLite.');
 $db=new SQLite3($path,SQLITE3_OPEN_READONLY);$db->enableExceptions(true);$db->busyTimeout(1000);$db->exec('PRAGMA trusted_schema=OFF; PRAGMA query_only=ON; PRAGMA cache_size=-2048;');
 foreach(['gpkg_contents','gpkg_geometry_columns','gpkg_spatial_ref_sys'] as $name)giTable($db,$name);return $db;
}
function giLayers(SQLite3 $db):array{
 $r=$db->query("SELECT g.table_name,g.column_name,g.geometry_type_name,g.srs_id,s.organization,s.organization_coordsys_id,c.identifier FROM gpkg_geometry_columns g JOIN gpkg_contents c ON c.table_name=g.table_name JOIN gpkg_spatial_ref_sys s ON s.srs_id=g.srs_id WHERE c.data_type='features' LIMIT 101");$out=[];
 while($v=$r->fetchArray(SQLITE3_ASSOC)){if(count($out)>=100)throw new InvalidArgumentException('Nejvýše 100 vrstev v souboru.');$cols=giTable($db,$v['table_name']);if(!isset($cols[$v['column_name']]))throw new InvalidArgumentException('Chybí sloupec geometrie.');$epsg=strtoupper((string)$v['organization'])==='EPSG'?(int)$v['organization_coordsys_id']:0;$type=strtoupper($v['geometry_type_name']);$supported=in_array($epsg,[4326,3857,5514],true)&&in_array($type,['POINT','MULTIPOINT','POLYGON','MULTIPOLYGON'],true);$out[]=['name'=>$v['table_name'],'label'=>$v['identifier']?:$v['table_name'],'geometry_column'=>$v['column_name'],'type'=>$type,'srs_id'=>(int)$v['srs_id'],'epsg'=>$epsg,'supported'=>$supported,'kind'=>in_array($type,['POINT','MULTIPOINT'],true)?'trees':'areas'];}return $out;
}
function giGeometry(?string $blob,int $srs):?array{
 if($blob===null||$blob==='')return null;if(strlen($blob)<8||strlen($blob)>100000||substr($blob,0,2)!=='GP'||ord($blob[2])!==0)throw new InvalidArgumentException('Neplatná geometrie GeoPackage.');
 $flags=ord($blob[3]);if($flags&224)throw new InvalidArgumentException('Rozšířená geometrie není podporována.');$env=($flags>>1)&7;$sizes=[0,32,48,48,64];if(!isset($sizes[$env]))throw new InvalidArgumentException('Neplatná obálka geometrie.');
 $sr=unpack(($flags&1?'V':'N').'s',substr($blob,4,4))['s'];if($sr>2147483647)$sr-=4294967296;if($sr!==$srs)throw new InvalidArgumentException('Geometrie má jiný souřadnicový systém než vrstva.');if($flags&16)return null;
 $offset=8+$sizes[$env];$count=0;
 $read=function(int $depth=0)use(&$read,&$offset,&$count,$blob):array{
  if($depth>1||$offset+5>strlen($blob))throw new InvalidArgumentException('Poškozená geometrie.');$order=ord($blob[$offset++]);if($order>1)throw new InvalidArgumentException('Neplatné pořadí bajtů geometrie.');
  $uint=function()use(&$offset,$order,$blob):int{if($offset+4>strlen($blob))throw new InvalidArgumentException('Neúplná geometrie.');$v=unpack(($order?'V':'N').'v',substr($blob,$offset,4))['v'];$offset+=4;return $v;};
  $type=$uint();$dim=intdiv($type,1000);$base=$type%1000;if($dim>3)throw new InvalidArgumentException('Nepodporovaný typ geometrie.');$dims=$dim===0?2:($dim===3?4:3);
  $point=function()use(&$offset,&$count,$blob,$order,$dims):array{if(++$count>1001||$offset+$dims*8>strlen($blob))throw new InvalidArgumentException('Geometrie má více než 1000 vrcholů nebo je neúplná.');$xy=[];for($i=0;$i<$dims;$i++){$v=unpack(($order?'e':'E').'v',substr($blob,$offset,8))['v'];$offset+=8;if($i<2){if(!is_finite($v))throw new InvalidArgumentException('Neplatná souřadnice.');$xy[]=$v;}}return $xy;};
  if($base===1)return ['type'=>'Point','coordinates'=>$point()];
  if($base===4){if($uint()!==1)throw new InvalidArgumentException('MultiPoint obsahuje více bodů nebo je prázdný. Rozdělte jej na jednotlivé objekty.');$g=$read($depth+1);if($g['type']!=='Point')throw new InvalidArgumentException('Neplatný MultiPoint.');return $g;}
  if($base===3){$rings=$uint();if($rings!==1)throw new InvalidArgumentException('Plocha musí mít jeden obrys bez otvorů.');$n=$uint();if($n<4||$n>1001)throw new InvalidArgumentException('Plocha musí mít 3 až 1000 vrcholů.');$ring=[];for($i=0;$i<$n;$i++)$ring[]=$point();return ['type'=>'Polygon','coordinates'=>[$ring]];}
  if($base===6){if($uint()!==1)throw new InvalidArgumentException('Vícedílnou plochu nejprve rozdělte na samostatné objekty.');$g=$read($depth+1);if($g['type']!=='Polygon')throw new InvalidArgumentException('Neplatný MultiPolygon.');return $g;}
  throw new InvalidArgumentException('Podporovány jsou body a jednoduché polygony.');
 };$g=$read();if($offset!==strlen($blob))throw new InvalidArgumentException('Geometrie obsahuje neočekávaná data.');return $g;
}
function giRead(SQLite3 $db,array $layer):array{
 if(!$layer['supported'])throw new InvalidArgumentException('Podporovány jsou POINT / jednobodový MULTIPOINT / POLYGON / jednoduchý MULTIPOLYGON v EPSG:4326, 3857 nebo 5514.');
 $cols=giTable($db,$layer['name']);$geometry=$layer['geometry_column'];$headers=array_values(array_filter(array_keys($cols),fn($c)=>$c!==$geometry));$limit=$layer['kind']==='areas'?50:500;
 $r=$db->query('SELECT * FROM '.giIdent($layer['name']).' LIMIT '.($limit+1));$rows=[];$geometries=[];$bytes=0;
 while($v=$r->fetchArray(SQLITE3_ASSOC)){if(count($rows)>=$limit)throw new InvalidArgumentException('Vrstva může mít nejvýše '.$limit.' objektů. Exportujte menší výběr.');$row=[];foreach($headers as $h){$value=$v[$h];if(is_string($value)&&(!mb_check_encoding($value,'UTF-8')||strlen($value)>20000))throw new InvalidArgumentException('Neplatný nebo příliš dlouhý atribut '.$h);$bytes+=strlen((string)$value);if($bytes>2200000)throw new InvalidArgumentException('Atributy jsou příliš rozsáhlé. Exportujte menší výběr.');$row[]=$value;}
 try{$g=giGeometry($v[$geometry],$layer['srs_id']);if($g&&($layer['kind']==='trees')!==($g['type']==='Point'))throw new InvalidArgumentException('Typ geometrie neodpovídá vrstvě.');}catch(Throwable $e){throw new InvalidArgumentException('Objekt '.(count($rows)+1).': '.$e->getMessage());}$rows[]=$row;$geometries[]=$g;}
 if(!$rows)throw new InvalidArgumentException('Vrstva je prázdná.');return ['headers'=>$headers,'rows'=>$rows,'geometries'=>$geometries,'epsg'=>$layer['epsg'],'kind'=>$layer['kind'],'missing'=>count(array_filter($geometries,fn($g)=>$g===null))];
}
