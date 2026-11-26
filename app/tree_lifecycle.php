<?php
declare(strict_types=1);
function svmEventDate(mixed $value): ?string {
    if ($value === null || $value === '') return null;
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) throw new InvalidArgumentException('Zadejte platné datum.');
    [$y,$m,$d]=array_map('intval',explode('-',$value));
    if ($y<1000 || !checkdate($m,$d,$y)) throw new InvalidArgumentException('Zadejte platné datum.');
    return $value;
}
// Record the transition, not every subsequent edit. No historical backfill.
function svmDeathTransition(array $old, array $next, string $now): array {
    $was=(string)($old['vitality']??'')==='5'||($old['removal_type']??'')==='dead';
    $is=(string)($next['vitality']??'')==='5'||($next['removal_type']??'')==='dead';
    if ($is&&!$was) return ['death_marked_at'=>$now];
    if (!$is&&$was) return ['death_marked_at'=>null,'death_date'=>null];
    return [];
}
function svmTreeEvent(array $tree): ?array {
    $type=(string)($tree['removal_type']??'');
    $labels=['dead'=>['Odumřel','Datum odumření','Označeno jako odumřelé dne'], 'felled'=>['Pokácen','Datum pokácení','Označeno jako pokácené dne'], 'fallen'=>['Vyvrácen / zlomen','Datum vyvrácení / zlomení','Označeno jako vyvrácené / zlomené dne'], 'missing'=>['Nenalezen','Datum zjištění nepřítomnosti','Označeno jako nenalezené dne'], 'other'=>['Jiný důvod','Datum odstranění','Označeno jako odstraněné dne']];
    if (!isset($labels[$type])) $type=(string)($tree['vitality']??'')==='5'?'dead':'';
    if ($type==='') return null;
    [$label,$dateLabel,$markedLabel]=$labels[$type];
    $date=$tree[$type==='dead'?'death_date':'removal_date']??null;
    $marked=$tree[$type==='dead'?'death_marked_at':'removal_marked_at']??null;
    $value=$date?:($marked?substr($marked,0,10):null);
    return ['label'=>$label,'date_label'=>$date?$dateLabel:$markedLabel,'date'=>$value];
}
