<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
if(session_status()!==PHP_SESSION_ACTIVE)session_start([
 'use_strict_mode'=>true,'cookie_httponly'=>true,'cookie_samesite'=>'Lax',
 'cookie_secure'=>parse_url(svmUrl(),PHP_URL_SCHEME)==='https'
]);
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
$_SESSION['auth_form_token']??=bin2hex(random_bytes(32));
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 $token=$_POST['auth_form_token']??null;
 if(!is_string($token)||!hash_equals($_SESSION['auth_form_token'],$token)){
  http_response_code(403);exit('Platnost formuláře vypršela. Obnovte stránku a odešlete formulář znovu.');
 }
}
