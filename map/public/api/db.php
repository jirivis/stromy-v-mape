<?php
require_once dirname(__DIR__,3).'/app/bootstrap.php';
if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){http_response_code(403);exit;}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try{$db=svmConfig()['database'];$mysqli=new mysqli($db['host'],$db['user'],$db['password'],$db['name'],(int)($db['port']??3306));$mysqli->set_charset('utf8mb4');}
catch(Throwable $e){error_log('Database connection failed: '.$e->getMessage());http_response_code(503);exit('Databáze není dostupná. Zkontrolujte konfiguraci.');}
