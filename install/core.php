<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/sql.php';
function siRequirements():array{
 $out=['PHP 8.2 nebo novější'=>PHP_VERSION_ID>=80200];foreach(['mysqli','mbstring','fileinfo','gd','zip','sqlite3','dom','openssl'] as $ext)$out['PHP '.$ext]=extension_loaded($ext);
 $out['mysqli s get_result (mysqlnd)']=function_exists('mysqli_stmt_get_result');
 foreach(['private','private/storage/area_photos','map/public/photos','map/public/uploads'] as $dir)$out['Zápis do '.$dir]=is_dir(dirname(__DIR__).'/'.$dir)&&is_writable(dirname(__DIR__).'/'.$dir);
 return $out;
}
function siAdmin(array $d):array{
 $name=trim((string)($d['username']??''));$email=trim((string)($d['email']??''));$password=(string)($d['password']??'');
 if(!preg_match('/^[A-Za-z0-9_.-]{3,50}$/D',$name))throw new InvalidArgumentException('Jméno: 3–50 znaků, písmena bez diakritiky, číslice, tečka, pomlčka nebo podtržítko.');
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>100)throw new InvalidArgumentException('Zadejte platný e-mail do 100 znaků.');
 if(strlen($password)<12||strlen($password)>72||str_contains($password,"\0"))throw new InvalidArgumentException('Heslo musí mít 12–72 bajtů a nesmí obsahovat nulový znak.');
 if(!hash_equals($password,(string)($d['password_again']??'')))throw new InvalidArgumentException('Hesla se neshodují.');return [$name,$email,password_hash($password,PASSWORD_DEFAULT)];
}
function siInstall(mysqli $db,string $schema,array $admin):void{
 // Never install into an existing or partly initialized database.
 if($db->query('SHOW TABLES')->num_rows!==0)throw new RuntimeException('Databáze není prázdná. Instalace nebyla spuštěna; existující data se nemažou.');
 foreach(siStatements($schema) as $sql)$db->query($sql);
 $db->query('CREATE TABLE svm_schema_migrations (version VARCHAR(80) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 $db->begin_transaction();try{
  [$name,$email,$hash]=$admin;$s=$db->prepare("INSERT INTO users(username,email,password,role,is_super_admin,active) VALUES(?,?,?,'admin',1,1)");$s->bind_param('sss',$name,$email,$hash);$s->execute();$s->close();
  $db->query("INSERT INTO svm_schema_migrations(version) VALUES('baseline-2.23.0')");$db->commit();
 }catch(Throwable $e){$db->rollback();throw $e;}
}
