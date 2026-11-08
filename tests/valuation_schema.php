<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/sql.php';
require dirname(__DIR__).'/app/update_database.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','','',(int)$argv[1]);
$db->query('CREATE DATABASE valuation_test');$db->select_db('valuation_test');
foreach(['trees','svm_areas'] as $t){$db->query("CREATE TABLE $t (id INT PRIMARY KEY, project_id INT NOT NULL)");$db->query("INSERT INTO $t VALUES (17,4)");}
$sql=file_get_contents(dirname(__DIR__).'/database/migrations/07_valuation_prepare.sql');
foreach(siStatements($sql) as $statement)$db->query($statement);
foreach(['trees','svm_areas'] as $t){
 $r=$db->query("SELECT * FROM $t")->fetch_assoc();
 if($r['valuation_selected']!=0||$r['valuation_value_czk']!==null||$r['id']!=17||$r['project_id']!=4)throw new Exception('Defaults/data changed');
 $db->query("UPDATE $t SET valuation_selected=1, valuation_value_czk=0");
}
foreach(siStatements($sql) as $statement)$db->query($statement);
foreach(['trees','svm_areas'] as $t){$r=$db->query("SELECT * FROM $t")->fetch_assoc();if($r['valuation_selected']!=1||$r['valuation_value_czk']!=='0')throw new Exception('Repeated migration lost data');}
$checks=json_decode(file_get_contents(dirname(__DIR__).'/database/migrations/07_valuation_checks.json'),true,512,JSON_THROW_ON_ERROR);
upDatabasePreflight(['migrations'=>['test.sql'],'migration_mode'=>'manual-schema','schema_checks'=>$checks],$db);
$db->query('CREATE DATABASE valuation_fresh');$db->select_db('valuation_fresh');
foreach(siStatements(file_get_contents(dirname(__DIR__).'/database/schema.sql')) as $statement)$db->query($statement);
upDatabasePreflight(['migrations'=>['test.sql'],'migration_mode'=>'manual-schema','schema_checks'=>$checks],$db);
echo "PASS: valuation migration defaults, IDs and project preserved; repeat preserves selection and zero value; fresh schema matches.\n";
