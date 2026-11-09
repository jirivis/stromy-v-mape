<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/install/core.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','','',(int)$argv[1]);
$db->query('CREATE DATABASE catalogs_test');$db->select_db('catalogs_test');$db->set_charset('utf8mb4');
siInstall($db,file_get_contents(dirname(__DIR__).'/database/schema.sql'),['tester','test@example.invalid',password_hash('test-only-password',PASSWORD_DEFAULT)]);
function countRows(mysqli $db,string $table):int{return (int)$db->query('SELECT COUNT(*) FROM '.$table)->fetch_row()[0];}
if(countRows($db,'species')!==557||countRows($db,'cultivars')!==1099)throw new Exception('Fresh install catalog counts: '.countRows($db,'species').'/'.countRows($db,'cultivars'));
$db->query("UPDATE species SET czech_name='Místní úprava' WHERE scientific_name='Abies alba'");
$db->query("UPDATE cultivars SET notes='Místní poznámka' WHERE name='Globosum'");
$before=[];foreach(['species','cultivars'] as $t)$before[$t]=$db->query("SELECT * FROM $t ORDER BY id")->fetch_all(MYSQLI_ASSOC);
foreach(['baseline.sql','additions.sql'] as $f)foreach(siStatements(file_get_contents(dirname(__DIR__).'/database/catalogs/'.$f)) as $sql)$db->query($sql);
foreach(['species','cultivars'] as $t)if($before[$t]!==$db->query("SELECT * FROM $t ORDER BY id")->fetch_all(MYSQLI_ASSOC))throw new Exception('Repeat changed IDs or user edits');
$db->query("DELETE FROM species WHERE scientific_name='Parrotia persica'");
foreach(siStatements(file_get_contents(dirname(__DIR__).'/database/catalogs/additions.sql')) as $sql)$db->query($sql);
if(countRows($db,'species')!==557)throw new Exception('Missing entry not restored');
echo "PASS: fresh catalog installation; repeat preserves IDs and edits; missing species added.\n";
