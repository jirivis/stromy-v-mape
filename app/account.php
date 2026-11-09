<?php
declare(strict_types=1);
// Refresh identity and system privileges from the database on every protected request.
function svmLoadAccount(mysqli $db,int $id):?array{
 if($id<=0)return null;$s=$db->prepare('SELECT password,id,username,role,is_super_admin,active FROM users WHERE id=? LIMIT 1');$s->bind_param('i',$id);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();if(!$row||(int)$row['active']!==1||!isset($_SESSION['password_version'])||!hash_equals(hash('sha256',(string)$row['password']),(string)$_SESSION['password_version']))return null;
 unset($row['password']);return $row;
}
function svmApplyAccount(?array $row):bool{
 if(!$row){foreach(['password_version','user_id','username','role','is_super_admin','active','active_project_id','active_project_name','active_project_role'] as $key)unset($_SESSION[$key]);return false;}
 foreach(['username','role','is_super_admin','active'] as $key)$_SESSION[$key]=$row[$key];$_SESSION['user_id']=(int)$row['id'];return true;
}
