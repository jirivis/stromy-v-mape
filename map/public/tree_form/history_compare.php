<?php
// The editor already verified access to this tree and loaded its history.
$compareFields=json_decode(file_get_contents(__DIR__.'/../project_tools/tree_fields.json'),true,64,JSON_THROW_ON_ERROR);
unset($compareFields['lat_wgs84'],$compareFields['lng_wgs84'],$compareFields['x_sjtsk'],$compareFields['y_sjtsk']);
$compareFields=['usr_id'=>['label'=>'Číslo stromu','type'=>'number']]+$compareFields+['status'=>['label'=>'Stav','options'=>['active'=>'Existující','removed'=>'Odstraněný']]];
$compareRecords=[['label'=>'Aktuální uložený stav','saved'=>'','author'=>'','values'=>array_intersect_key($tree,$compareFields)]];
foreach($treeHistory as $h){$data=json_decode((string)$h['tree_data'],true);if(!is_array($data))continue;$compareRecords[]=['label'=>(string)$h['snapshot_at'].($h['history_note']?' · '.$h['history_note']:''),'saved'=>(string)$h['snapshot_at'],'author'=>(string)($h['username']??''),'values'=>array_intersect_key($data,$compareFields)];}
?>
<details class="history-compare" id="history-compare"><summary>Porovnat záznamy a zobrazit vývoj</summary>
<p>Zaškrtněte záznamy a údaje, které chcete porovnat. Aktuální stav znamená poslední uložené hodnoty; neuložené změny formuláře se nezahrnují.</p>
<div class="history-compare-options"><fieldset id="compare-records"><legend>Záznamy</legend></fieldset><fieldset id="compare-fields"><legend>Porovnávané údaje</legend></fieldset></div>
<p id="compare-status" role="status"></p><div class="history-compare-table" id="compare-table"></div><div id="compare-charts"></div>
<p>Grafy ukazují naměřené hodnoty, nikoli automaticky roční přírůst. Pořadí měření odpovídá datu měření; pokud chybí, je použit čas uložení historie. Změna místa měření nebo metodiky může ovlivnit rozdíly. Chybějící hodnoty nejsou nuly.</p></details>
<script type="application/json" id="history-compare-data"><?=json_encode(['fields'=>$compareFields,'records'=>$compareRecords],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)?></script>
<link rel="stylesheet" href="tree_form/history_compare.css?v=2.20"><script defer src="tree_form/history_compare.js?v=2.20"></script>
