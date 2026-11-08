<?php
declare(strict_types=1);
// Signed postconditions for additive schema migrations. No SQL is executed here.
function upSchemaChecks(array $manifest):array{
 $migrations=$manifest['migrations']??[];
 if(!is_array($migrations))throw new RuntimeException('Neplatný seznam databázových migrací.');
 if(!$migrations)return [];
 $checks=$manifest['schema_checks']??[];
 if(($manifest['migration_mode']??'')!=='manual-schema'||!is_array($checks)||!array_is_list($checks)||!$checks)
  throw new RuntimeException('Balíček nemá ověřitelný postup přípravy databáze. Vyžádejte si nový balíček od vydavatele.');
 foreach($checks as $c){
  if(!is_array($c)||!preg_match('/^[A-Za-z0-9_]+$/D',$c['table']??'')||!preg_match('/^[A-Za-z0-9_]+$/D',$c['column']??'')||!is_string($c['type']??null)||!preg_match('/^[a-z0-9(), ]+$/D',$c['type'])||!is_bool($c['nullable']??null))
   throw new RuntimeException('Neplatná kontrola databázové struktury v balíčku.');
 }
 return $checks;
}
function upDatabasePreflight(array $manifest,mysqli $db):void{
 foreach(upSchemaChecks($manifest) as $c){
  $s=$db->prepare('SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
  $s->bind_param('ss',$c['table'],$c['column']);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();
  if(!$row||strtolower($row['COLUMN_TYPE'])!==$c['type']||($row['IS_NULLABLE']==='YES')!==$c['nullable'])
   throw new RuntimeException('Databáze ještě není připravená: '.$c['table'].'.'.$c['column'].'. Použijte SQL přípravu přes správce databáze a zopakujte kontrolu.');
 }
}
function upMigrationSql(string $zipPath,array $manifest):string{
 upSchemaChecks($manifest);
 if(empty($manifest['migrations']))throw new RuntimeException('Balíček neobsahuje databázové změny.');
 $z=new ZipArchive();if($z->open($zipPath)!==true)throw new RuntimeException('Balíček nelze otevřít.');
 try{
  $sql="-- Stromy v mape: priprava databaze pro ".$manifest['version']."\n-- Spoustet pouze po zaloze, ve spravne databazi, uctem spravce.\n-- Pri chybe nepokracujte instalaci. Aplikace pak overi strukturu.\n\n";
  foreach($manifest['migrations'] as $p){$bytes=$z->getFromName('payload/'.$p);if($bytes===false||!hash_equals($manifest['files'][$p],hash('sha256',$bytes)))throw new RuntimeException('SQL soubor se změnil. Připravte balíček znovu.');$sql.="-- ".$p."\n".$bytes."\n;\n\n";}
  return $sql;
 }finally{$z->close();}
}
