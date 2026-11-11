<?php
function exPhotoGalleries(ZipArchive $zip,array &$trees,array &$areas,array $photos):void{
 foreach(['strom'=>&$trees,'plocha'=>&$areas] as $kind=>&$rows){foreach($rows as &$row){$list=array_values(array_filter($photos,fn($p)=>$p['object_type']===$kind&&(string)$p['object_id']===(string)$row['id']&&$p['export_status']==='zahrnuto'));$row['foto_hlavni']=$list[0]['export_path']??null;$row['fotogalerie']=$list?'galerie/'.$kind.'_'.rawurlencode((string)($row['usr_id']??$row['code']??'')).'.html':null;if(!$list)continue;
 $escape=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$title=($kind==='strom'?'Strom ':'Plocha ').($row['usr_id']??$row['code']??'');$html='<!doctype html><html lang="cs"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$escape($title).'</title><style>body{font:16px sans-serif;margin:24px;color:#234732}main{display:flex;flex-wrap:wrap;gap:24px}figure{margin:0;max-width:480px}img{max-width:100%;max-height:70vh}figcaption{white-space:pre-wrap}</style><h1>'.$escape($title).'</h1><main>';
 foreach($list as $p){$url='../'.implode('/',array_map('rawurlencode',explode('/',$p['export_path'])));$html.='<figure><a href="'.$escape($url).'"><img loading="lazy" src="'.$escape($url).'" alt="'.$escape(basename($p['export_path'])).'"></a><figcaption>'.$escape($p['note']?:basename($p['export_path'])).'</figcaption></figure>';}$zip->addFromString($row['fotogalerie'],$html.'</main></html>');
 }unset($row);}unset($rows);
}

// Strip database identities only after all database-backed associations are resolved.
function exPublicRows(array &$trees,array &$areas,array &$photos,array &$species,array &$parts):void{
 foreach([&$trees,&$areas] as &$rows){$seen=[];foreach($rows as &$r){$code=trim((string)($r['usr_id']??$r['code']??''));if($code===''||isset($seen[$code]))throw new RuntimeException('Export vyžaduje vyplněná a jedinečná evidenční čísla objektů.');$seen[$code]=true;foreach(['id','project_id','created_by','updated_by','user_id','photo_url'] as $key)unset($r[$key]);}unset($r);}unset($rows);
 foreach($photos as &$r){unset($r['object_id'],$r['photo_id'],$r['source_url']);$r['original_name']=basename($r['export_path']);}unset($r);
 foreach([&$species,&$parts] as &$rows){foreach($rows as &$r)unset($r['area_id'],$r['id'],$r['project_id']);unset($r);}unset($rows);
}
