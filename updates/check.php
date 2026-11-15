<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/auth.php';require_once dirname(__DIR__).'/app/update.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if((int)($_SESSION['is_super_admin']??0)!==1||($_SERVER['REQUEST_METHOD']??'')!=='POST'||!hash_equals($_SESSION['update_csrf']??'none',(string)($_POST['csrf']??''))){http_response_code(403);exit('{}');}
session_write_close();
try{
 $cfg=upConfig(svmConfig()['updates']??[]);
 if(empty($cfg['check_on_dashboard'])){echo '{}';exit;}
 $r=upCachedRelease($cfg);
 if(!$r||time()-(int)($r['checked_at']??0)>21600)$r=upCheck($cfg);
 echo json_encode(['available'=>version_compare($r['version'],upVersion(),'>'),'version'=>$r['version']],JSON_THROW_ON_ERROR);
}catch(Throwable $e){echo json_encode(['available'=>false,'unavailable'=>true]);}
