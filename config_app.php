<?php
require_once __DIR__.'/app/bootstrap.php';
define('APP_NAME',(string)(svmConfig()['app']['name']??'Stromy v mapě'));
define('APP_VERSION',trim(file_get_contents(__DIR__.'/app/VERSION')));define('APP_BUILD','2026.11');
define('APP_COPYRIGHT','© Stromy v mapě');define('APP_AUTHOR','Jiří Vištejn');
date_default_timezone_set(svmConfig()['app']['timezone']??'Europe/Prague');
