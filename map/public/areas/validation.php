<?php
declare(strict_types=1);
function areaText($value, int $max): string {
    if (!is_string($value) || strlen($value) > $max) throw new InvalidArgumentException('Text je příliš dlouhý nebo neplatný.');
    return trim($value);
}
function areaNumber($value, float $max, bool $nullable = true): ?float {
    if ($nullable && ($value === null || $value === '')) return null;
    if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < 0 || (float)$value > $max) throw new InvalidArgumentException('Číselná hodnota je mimo povolený rozsah.');
    return (float)$value;
}
function areaDate($value): string {
    $v = areaText($value,10);
    if ($v === '') return '';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d',$v);
    if (!$date || $date->format('Y-m-d') !== $v) throw new InvalidArgumentException('Neplatné datum.');
    return $v;
}
function areaOrientation(array $a,array $b,array $c): float {
    return ($b[0]-$a[0])*($c[1]-$a[1])-($b[1]-$a[1])*($c[0]-$a[0]);
}
function areaOnSegment(array $a,array $b,array $c): bool {
    return abs(areaOrientation($a,$b,$c)) < 1e-14 && $c[0]>=min($a[0],$b[0])-1e-14 && $c[0]<=max($a[0],$b[0])+1e-14 && $c[1]>=min($a[1],$b[1])-1e-14 && $c[1]<=max($a[1],$b[1])+1e-14;
}
function areaSegmentsCross(array $a,array $b,array $c,array $d): bool {
    $o1=areaOrientation($a,$b,$c);$o2=areaOrientation($a,$b,$d);$o3=areaOrientation($c,$d,$a);$o4=areaOrientation($c,$d,$b);
    return (($o1>0 && $o2<0 || $o1<0 && $o2>0) && ($o3>0 && $o4<0 || $o3<0 && $o4>0)) || areaOnSegment($a,$b,$c) || areaOnSegment($a,$b,$d) || areaOnSegment($c,$d,$a) || areaOnSegment($c,$d,$b);
}
function areaGeometry($g): array {
    if (!is_array($g) || ($g['type']??'')!=='Polygon' || !isset($g['coordinates']) || !is_array($g['coordinates']) || count($g['coordinates'])!==1) throw new InvalidArgumentException('Nakreslete jeden polygon bez otvorů.');
    $ring=$g['coordinates'][0];
    if (!is_array($ring) || count($ring)<4 || count($ring)>1001) throw new InvalidArgumentException('Polygon musí mít 3 až 1000 vrcholů.');
    foreach($ring as &$p) {
        if (!is_array($p) || count($p)!==2 || !isset($p[0],$p[1]) || !is_numeric($p[0]) || !is_numeric($p[1])) throw new InvalidArgumentException('Neplatné souřadnice.');
        $p=[(float)$p[0],(float)$p[1]];
        if (!is_finite($p[0]) || !is_finite($p[1]) || abs($p[0])>180 || abs($p[1])>=85) throw new InvalidArgumentException('Souřadnice jsou mimo rozsah.');
    } unset($p);
    $n=count($ring)-1;
    if ($ring[0]!==$ring[$n]) throw new InvalidArgumentException('Polygon není uzavřený.');
    $seen=[];
    for($i=0;$i<$n;$i++) {
        $key=json_encode($ring[$i]);
        if(isset($seen[$key])) throw new InvalidArgumentException('Polygon má opakovaný vrchol.');
        $seen[$key]=true;
        if(abs($ring[$i][0]-$ring[$i+1][0])>180) throw new InvalidArgumentException('Polygon přes datovou hranici není podporován.');
        for($j=$i+1;$j<$n;$j++) {
            if($j===$i+1 || ($i===0 && $j===$n-1)) continue;
            if(areaSegmentsCross($ring[$i],$ring[$i+1],$ring[$j],$ring[$j+1])) throw new InvalidArgumentException('Hranice polygonu se kříží nebo dotýká sama sebe.');
        }
    }
    $sum=0.0;
    for($i=0;$i<$n;$i++) $sum+=deg2rad($ring[$i+1][0]-$ring[$i][0])*(2+sin(deg2rad($ring[$i][1]))+sin(deg2rad($ring[$i+1][1])));
    $m2=abs($sum*6378137**2/2);
    if($m2<0.01 || $m2>1e9) throw new InvalidArgumentException('Výměra polygonu je příliš malá nebo velká.');
    return [['type'=>'Polygon','coordinates'=>[$ring]],round($m2,2)];
}
function areaAttributes($a): array {
    if(!is_array($a)) throw new InvalidArgumentException('Chybí údaje plochy.');
    $out=['methodology'=>'AOPK_2022'];
    foreach(['name'=>200,'state_note'=>5000,'treatments'=>5000,'note'=>5000] as $key=>$max) $out[$key]=areaText($a[$key]??'',$max);
    foreach(['planted','measured_at','last_insp_at','next_insp_at'] as $key) $out[$key]=areaDate($a[$key]??'');
    foreach(['height_min','height_max'] as $key) $out[$key]=areaNumber($a[$key]??null,150);
    if($out['height_min']!==null && $out['height_max']!==null && $out['height_min']>$out['height_max']) throw new InvalidArgumentException('Dolní výška nesmí být větší než horní.');
    $enums=['suitability'=>['invasive','undesirable','other','suitable'],'management'=>['neglected','irregular','managed'],'biology'=>['low','medium','high'],'attractiveness'=>['low','medium','high'],'urgency'=>['0','1','2','3'],'status'=>['existing','removed']];
    foreach($enums as $k=>$allowed) {
        $v=$a[$k]??'';if(!is_string($v) || ($v!=='' && !in_array($v,$allowed,true))) throw new InvalidArgumentException('Neplatná volba: '.$k);
        $out[$k]=$v;
    }
    $out['components']=[];$parts=$a['components']??[];
    if(!is_array($parts) || count($parts)>7) throw new InvalidArgumentException('Neplatné části porostu.');
    foreach($parts as $p) {
        if(!is_array($p) || !in_array($p['type']??'', ['shrubs_low','shrubs_high','climbers','young','middle','mature','layered'],true)) throw new InvalidArgumentException('Neplatný charakter porostu.');
        if(isset($out['components'][$p['type']])) throw new InvalidArgumentException('Charakter porostu je zadán dvakrát.');
        $out['components'][$p['type']]=['type'=>$p['type'],'area_m2'=>areaNumber($p['area_m2']??null,1e9,false)];
    }
    $out['components']=array_values($out['components']);
    $out['species']=[];$species=$a['species']??[];
    if(!is_array($species)||count($species)>100) throw new InvalidArgumentException('Příliš mnoho druhů.');
    $sum=0;
    foreach($species as $p) {
        if(!is_array($p)) throw new InvalidArgumentException('Neplatný druh.');
        $row=['scientific_name'=>areaText($p['scientific_name']??'',250),'czech_name'=>areaText($p['czech_name']??'',250),'share'=>areaNumber($p['share']??null,100)];
        if($row['scientific_name']==='' && $row['czech_name']==='') {
            if($row['share']!==null) throw new InvalidArgumentException('U zadaného zastoupení doplňte název druhu, nebo zastoupení vymažte.');
            continue; // Completely empty species rows are optional.
        }
        $sum+=$row['share']??0;$out['species'][]=$row;
    }
    if($sum>100.001) throw new InvalidArgumentException('Součet zastoupení druhů přesahuje 100 %.');
    return $out;
}
