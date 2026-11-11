<?php
// Read only photographs already included in the authorized export ZIP.
// Stream the embedded bytes to disk instead of keeping a whole gallery in memory.
function exGalleryImage(ZipArchive $zip,string $path,$out):void{
 $in=$zip->getStream($path);
 if($in===false)throw new RuntimeException('Nelze načíst fotografii do galerie.');
 try{
  $head=fread($in,8192);
  if($head===false)throw new RuntimeException('Nelze číst fotografii.');
  $mime=(new finfo(FILEINFO_MIME_TYPE))->buffer($head);
  if(!in_array($mime,['image/jpeg','image/png','image/webp','image/gif'],true))throw new RuntimeException('Nepodporovaný formát fotografie v galerii.');
  fwrite($out,'data:'.$mime.';base64,');
  $filter=stream_filter_append($out,'convert.base64-encode',STREAM_FILTER_WRITE);
  if($filter===false)throw new RuntimeException('Nelze vytvořit vloženou fotografii.');
  try{
   if(fwrite($out,$head)!==strlen($head)||stream_copy_to_stream($in,$out)===false)throw new RuntimeException('Nelze zapsat fotografii do galerie.');
  }finally{stream_filter_remove($filter);}
 }finally{fclose($in);}
}
function exPhotoGalleries(ZipArchive $zip,array &$trees,array &$areas,array $photos,callable $temp):void{
 $grouped=[];
 foreach($photos as $photo)if($photo['export_status']==='zahrnuto')$grouped[$photo['object_type']][(string)$photo['object_id']][]=$photo;
 $escape=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
 foreach(['strom'=>&$trees,'plocha'=>&$areas] as $kind=>&$rows){
  foreach($rows as &$row){
   $list=$grouped[$kind][(string)$row['id']]??[];
   $row['foto_hlavni']=$list[0]['export_path']??null;
   $row['fotogalerie']=$list?'galerie/'.$kind.'_'.rawurlencode((string)($row['usr_id']??$row['code']??'')).'.html':null;
   if(!$list)continue;
   $title=($kind==='strom'?'Strom ':'Plocha ').($row['usr_id']??$row['code']??'');
   $file=$temp();$out=fopen($file,'wb');
   if($out===false)throw new RuntimeException('Nelze vytvořit galerii.');
   try{
    fwrite($out,'<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$escape($title).'</title><style>body{font:16px sans-serif;margin:24px;color:#234732}main{display:flex;flex-wrap:wrap;gap:24px}figure{margin:0;max-width:480px}img{max-width:100%;max-height:70vh}figcaption{white-space:pre-wrap}figure:target{max-width:100%;width:100%}figure:target img{max-height:none;width:auto}a{color:inherit}</style></head><body><h1 id="prehled">'.$escape($title).'</h1><p>Fotografie jsou vložené přímo v této galerii. Klepnutím obrázek zvětšíte.</p><main>');
    foreach($list as $i=>$p){
     $id='foto_'.($i+1);
     fwrite($out,'<figure id="'.$id.'"><a href="#'.$id.'"><img loading="lazy" src="');
     exGalleryImage($zip,$p['export_path'],$out);
     fwrite($out,'" alt="'.$escape(basename($p['export_path'])).'"></a><figcaption>'.$escape($p['note']?:basename($p['export_path'])).'</figcaption><p><a href="#prehled">Zpět na přehled</a></p></figure>');
    }
    fwrite($out,'</main></body></html>');
   }finally{fclose($out);}
   if(!$zip->addFile($file,$row['fotogalerie']))throw new RuntimeException('Nelze přidat galerii do exportu.');
  }unset($row);
 }unset($rows);
}

// Strip database identities only after all database-backed associations are resolved.
function exPublicRows(array &$trees,array &$areas,array &$photos,array &$species,array &$parts):void{
 foreach([&$trees,&$areas] as &$rows){$seen=[];foreach($rows as &$r){$code=trim((string)($r['usr_id']??$r['code']??''));if($code===''||isset($seen[$code]))throw new RuntimeException('Export vyžaduje vyplněná a jedinečná evidenční čísla objektů.');$seen[$code]=true;foreach(['id','project_id','created_by','updated_by','user_id','photo_url'] as $key)unset($r[$key]);}unset($r);}unset($rows);
 foreach($photos as &$r){unset($r['object_id'],$r['photo_id'],$r['source_url']);$r['original_name']=basename($r['export_path']);}unset($r);
 foreach([&$species,&$parts] as &$rows){foreach($rows as &$r)unset($r['area_id'],$r['id'],$r['project_id']);unset($r);}unset($rows);
}
