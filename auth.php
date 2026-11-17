<?php
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
if(empty($_SESSION['user_id'])){header('Location: /login.php');exit;}
require_once __DIR__.'/map/public/api/db.php';require_once __DIR__.'/app/account.php';
if(!svmApplyAccount(svmLoadAccount($mysqli,(int)$_SESSION['user_id']))){header('Location: /login.php?error=session');exit;}
