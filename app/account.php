<?php
declare(strict_types=1);
// Refresh identity and system privileges from the database on every protected request.
function svmLoadAccount(mysqli $db,int $id):?array{
 if($id<=0)return null;$s=$db->prepare('SELECT id,username,role,is_super_admin,active FROM users WHERE id=? LIMIT 1');$s->bind_param('i',$id);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();return $row&&(int)$row['active']===1?$row:null;
}
function svmApplyAccount(?array $row):bool{
 if(!$row){foreach(['user_id','username','role','is_super_admin','active','active_project_id','active_project_name','active_project_role'] as $key)unset($_SESSION[$key]);return false;}
 foreach(['username','role','is_super_admin','active'] as $key)$_SESSION[$key]=$row[$key];$_SESSION['user_id']=(int)$row['id'];return true;
}
