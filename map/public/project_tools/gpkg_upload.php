<?php
require_once __DIR__.'/common.php';require_once __DIR__.'/gpkg_read.php';$db=null;
try{ptCheckPost();requireProjectRole(['owner','admin','editor']);if(!$canEdit)throw new RuntimeException('Nemáte právo importovat.',403);
 $f=$_FILES['file']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Soubor se nepodařilo nahrát. Zkontrolujte limit uploadu hostingu.');
 if(!is_uploaded_file($f['tmp_name'])||$f['size']>20*1024*1024||strtolower(pathinfo($f['name'],PATHINFO_EXTENSION))!=='gpkg')throw new InvalidArgumentException('Použijte samostatný soubor .gpkg do 20 MB.');
 $db=giOpen($f['tmp_name']);$layers=giLayers($db);$name=$_POST['layer']??null;
 if($name===null){$out=['layers'=>$layers];}else{$hits=array_values(array_filter($layers,fn($l)=>$l['name']===$name));if(count($hits)!==1)throw new InvalidArgumentException('Vrstva není v tomto souboru jednoznačná.');$out=giRead($db,$hits[0]);}
 $db->close();$db=null;ptOut($out+['project_id'=>$project]);
}catch(Throwable $e){if($db)$db->close();ptOut(['error'=>$e instanceof SQLite3Exception?'Soubor GPKG nelze přečíst.':$e->getMessage()],$e->getCode()===403?403:422);}
