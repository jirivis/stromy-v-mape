<?php
declare(strict_types=1);
function siStatements(string $sql):array{
 // Parse only our controlled schema, not arbitrary uploaded SQL. Keep generated expressions intact.
 $out=[];$part='';$quote=null;$lineComment=false;$blockComment=false;$len=strlen($sql);
 for($i=0;$i<$len;$i++){$c=$sql[$i];$n=$sql[$i+1]??'';
  if($lineComment){if($c==="\n"){$lineComment=false;$part.="\n";}continue;}
  if($blockComment){if($c==='*'&&$n==='/'){$blockComment=false;$i++;$part.=' ';}continue;}
  if($quote!==null){$part.=$c;if($c==='\\'&&$i+1<$len){$part.=$sql[++$i];continue;}if($c===$quote){if($n===$quote){$part.=$sql[++$i];}else $quote=null;}continue;}
  if($c==='-'&&$n==='-'){$lineComment=true;$i++;continue;}if($c==='/'&&$n==='*'){$blockComment=true;$i++;continue;}
  if(in_array($c,["'",'"','`'],true)){$quote=$c;$part.=$c;continue;}
  if($c===';'){if(trim($part)!=='')$out[]=trim($part);$part='';}else $part.=$c;
 }
 if($quote!==null||$blockComment)throw new RuntimeException('Neúplný instalační SQL soubor.');if(trim($part)!=='')$out[]=trim($part);return $out;
}
