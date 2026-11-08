<?php
declare(strict_types=1);
// HTML page preflight only. APIs retain their JSON errors and all existing role checks.
function svmSafeReturnPath(mixed $value):?string{
 if(!is_string($value)||strlen($value)>2048||preg_match('/[\x00-\x20\x7f\\\\]/',$value))return null;
 $parts=parse_url($value);if($parts===false||isset($parts['host'])||isset($parts['scheme'])||isset($parts['fragment']))return null;
 $path=$parts['path']??'';
 if(!preg_match('~^/map/public/(?:index\.php|karta\.php|tree_new\.php|areas/(?:index\.php)?|project_tools/(?:area_card|cards|table|settings|templates|trees|export|import|overview)\.php)?$~D',$path))return null;
 return $value;
}
function svmRequirePageLogin():void{
 if(session_status()!==PHP_SESSION_ACTIVE)session_start();
 if(!empty($_SESSION['user_id']))return;
 $target=svmSafeReturnPath($_SERVER['REQUEST_URI']??'');
 if($target&&($_SERVER['REQUEST_METHOD']??'GET')==='GET')$_SESSION['svm_return_after_login']=['path'=>$target,'expires'=>time()+3600];
 header('Cache-Control: no-store');header('Location: /login.php',true,302);exit;
}
function svmResumePageAfterLogin():void{
 if(empty($_SESSION['user_id'])||!isset($_SESSION['svm_return_after_login']))return;
 $saved=$_SESSION['svm_return_after_login'];
 if(is_array($saved)&&($saved['expires']??0)>=time()&&svmSafeReturnPath($saved['path']??null)&&empty($_SESSION['active_project_id']))return;
 unset($_SESSION['svm_return_after_login']);
 if(!is_array($saved)||($saved['expires']??0)<time())return;
 $target=svmSafeReturnPath($saved['path']??null);if(!$target)return;
 header('Cache-Control: no-store');header('Location: '.$target,true,302);exit;
}
