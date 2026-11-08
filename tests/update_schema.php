<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
// Isolated MariaDB started by run_schema.py; never use a production database.
require dirname(__DIR__).'/app/update_database.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
function verify(bool $v,string $m):void{if(!$v)throw new RuntimeException($m);}
$admin=new mysqli('127.0.0.1','root','','',(int)$argv[1]);
$admin->query('CREATE DATABASE update_schema_test');
$admin->select_db('update_schema_test');
$admin->query('CREATE TABLE objects (id INT PRIMARY KEY)');
$admin->query("INSERT INTO objects VALUES(1)");
$admin->query("CREATE USER 'web'@'localhost' IDENTIFIED BY 'test-only'");
$admin->query("GRANT SELECT,INSERT,UPDATE,DELETE ON update_schema_test.* TO 'web'@'localhost'");
$web=new mysqli('127.0.0.1','web','test-only','update_schema_test',(int)$argv[1]);
$m=['migrations'=>['database/migrations/test.sql'],'migration_mode'=>'manual-schema','schema_checks'=>[['table'=>'objects','column'=>'caption','type'=>'varchar(80)','nullable'=>true]]];
$reject=function()use($m,$web):void{try{upDatabasePreflight($m,$web);}catch(RuntimeException $e){verify(str_contains($e->getMessage(),'objects.caption'),'Clear missing schema message');return;}throw new RuntimeException('Unprepared schema accepted');};
$reject();
$admin->query('ALTER TABLE objects ADD caption VARCHAR(40) NOT NULL DEFAULT \'\'');$reject();
$admin->query('ALTER TABLE objects MODIFY caption VARCHAR(80) NOT NULL');$reject();
$admin->query('ALTER TABLE objects MODIFY caption VARCHAR(80) NULL');
upDatabasePreflight($m,$web);
try{$web->query('ALTER TABLE objects ADD forbidden INT');throw new RuntimeException('Web unexpectedly has DDL rights');}catch(mysqli_sql_exception $e){verify($e->getCode()===1142,'DDL rejected by MariaDB');}
verify($web->query('SELECT COUNT(*) AS n FROM objects')->fetch_assoc()['n']==1,'Data retained');
// Recheck must notice changes after an earlier successful check.
$admin->query('ALTER TABLE objects DROP caption');$reject();
echo "PASS: restricted web account; missing/wrong type/wrong nullability rejected; prepared schema accepted; recheck notices changes; data retained.\n";
