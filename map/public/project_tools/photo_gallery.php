<?php
// Preserve the QGIS main image and photo-table associations without HTML galleries.
function exPhotoReferences(array &$trees,array &$areas,array $photos):void{
 $first=[];foreach($photos as $p)if($p['export_status']==='zahrnuto')$first[$p['object_type']][(string)$p['object_id']]??=$p['export_path'];
 foreach(['strom'=>&$trees,'plocha'=>&$areas] as $kind=>&$rows){foreach($rows as &$row){$row['foto_hlavni']=$first[$kind][(string)$row['id']]??null;unset($row['fotogalerie']);}unset($row);}unset($rows);
}

// Strip database identities only after all database-backed associations are resolved.
function exPublicRows(array &$trees,array &$areas,array &$photos,array &$species,array &$parts):void{
 foreach([&$trees,&$areas] as &$rows){$seen=[];foreach($rows as &$r){$code=trim((string)($r['usr_id']??$r['code']??''));if($code===''||isset($seen[$code]))throw new RuntimeException('Export vyžaduje vyplněná a jedinečná evidenční čísla objektů.');$seen[$code]=true;foreach(['id','project_id','created_by','updated_by','user_id','photo_url'] as $key)unset($r[$key]);}unset($r);}unset($rows);
 foreach($photos as &$r){unset($r['object_id'],$r['photo_id'],$r['source_url']);$r['original_name']=basename($r['export_path']);}unset($r);
 foreach([&$species,&$parts] as &$rows){foreach($rows as &$r)unset($r['area_id'],$r['id'],$r['project_id']);unset($r);}unset($rows);
}
