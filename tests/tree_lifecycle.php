<?php
declare(strict_types=1);
require __DIR__.'/../app/tree_lifecycle.php';
function check(bool $ok):void {if(!$ok)throw new RuntimeException('Lifecycle assertion failed');}
$now='2026-11-26 12:00:00';
check(svmDeathTransition(['vitality'=>4],['vitality'=>5],$now)===['death_marked_at'=>$now]);
check(svmDeathTransition(['vitality'=>5],['vitality'=>5],$now)===[]);
check(svmDeathTransition(['removal_type'=>'dead'],['removal_type'=>'dead','vitality'=>5],$now)===[]);
check(svmDeathTransition(['vitality'=>5],['vitality'=>3],$now)===['death_marked_at'=>null,'death_date'=>null]);
check(svmTreeEvent([])===null);
check(svmTreeEvent(['vitality'=>5])['date']===null);
$e=svmTreeEvent(['vitality'=>5,'death_marked_at'=>$now]);check($e['date']==='2026-11-26'&&$e['date_label']==='Označeno jako odumřelé dne');
$e=svmTreeEvent(['removal_type'=>'dead','death_date'=>'2026-10-10','death_marked_at'=>$now]);check($e['date']==='2026-10-10'&&$e['date_label']==='Datum odumření');
$e=svmTreeEvent(['removal_type'=>'felled','vitality'=>5,'removal_date'=>'2026-11-20']);check($e['label']==='Pokácen'&&$e['date']==='2026-11-20');
check(svmEventDate('')===null&&svmEventDate('2024-02-29')==='2024-02-29');
foreach(['2026-02-29','2026-13-01','yesterday',[]] as $v){try{svmEventDate($v);throw new RuntimeException('Accepted invalid date');}catch(InvalidArgumentException $e){}}
echo "PASS lifecycle dates, transitions and labels\n";
