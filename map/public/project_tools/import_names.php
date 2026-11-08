<?php
declare(strict_types=1);
require_once __DIR__.'/common.php';
try {
 if($_SERVER['REQUEST_METHOD']!=='GET')throw new RuntimeException('Použijte GET.',405);
 requireProjectRole(['owner','admin','editor']);if(!$canEdit)throw new RuntimeException('Nemáte právo importovat.',403);
 if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
 $rows=ptQuery('SELECT scientific_name,czech_name FROM species ORDER BY scientific_name LIMIT 10001')->get_result()->fetch_all(MYSQLI_ASSOC);
 if(count($rows)>10000)throw new RuntimeException('Číselník je příliš velký pro tento způsob doplnění.');
 ptOut(['project_id'=>$project,'names'=>$rows]);
}catch(Throwable $e){$code=in_array($e->getCode(),[403,405])?$e->getCode():500;if($code===500)error_log('Import names: '.$e->getMessage());ptOut(['error'=>$code===500?'Číselník druhů se nepodařilo načíst. Import můžete dokončit bez doplnění názvů.':$e->getMessage()],$code);}
