<?php
declare(strict_types=1);
// Call inside the same transaction as the area change.
function areaHistoryRecord(mysqli $db,array $row,string $action):void {
 $snapshot=['code'=>$row['code'],'attributes'=>json_decode($row['attributes_json'],true,64,JSON_THROW_ON_ERROR),'geometry'=>json_decode($row['geometry_json'],true,64,JSON_THROW_ON_ERROR),'area_m2'=>(float)$row['area_m2'],'version'=>(int)$row['version']];
 $json=json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 $project=(int)$row['project_id'];$area=(int)$row['id'];$user=(int)($_SESSION['user_id']??0);
 $s=$db->prepare('INSERT INTO svm_area_history(project_id,area_id,user_id,action,snapshot_json) VALUES(?,?,?,?,?)');$s->bind_param('iiiss',$project,$area,$user,$action,$json);$s->execute();
}
function areaHistoryBaseline(mysqli $db,array $row):void {
 $project=(int)$row['project_id'];$area=(int)$row['id'];$s=$db->prepare('SELECT id FROM svm_area_history WHERE project_id=? AND area_id=? LIMIT 1');$s->bind_param('ii',$project,$area);$s->execute();if(!$s->get_result()->fetch_assoc())areaHistoryRecord($db,$row,'baseline');
}
