<?php require_once __DIR__.'/page_access.php';svmRequirePageLogin();
require_once __DIR__.'/common.php';
$id=(int)($_GET['id']??0);
$row=ptQuery('SELECT id FROM svm_areas WHERE id=? AND project_id=?',[$id,$project])->get_result()->fetch_assoc();
if(!$row){http_response_code(404);header('Content-Type: text/html; charset=utf-8');exit('Porost nebyl v aktivním projektu nalezen.');}
ptHead('Karta porostu');
$fields=ptTableFields('areas',ptSettings()['hidden']['areas']??[]);
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<main class="porost-page"><div class="tools"><a href="../">← Hlavní mapa</a><?php if($canEdit): ?><a href="../areas/?id=<?=$id?>">Upravit porost</a><?php endif; ?><a href="cards.php?area=<?=$id?>">Tisk / PDF karty</a></div><h1 id="cardTitle" class="porost-card-title">Karta porostu</h1><p id="status" role="status">Načítám…</p><div id="cardValues"></div><section class="porost-section"><h2>Poloha a hranice</h2><div id="cardMap"></div></section><section class="porost-section"><h2>Fotografie</h2><div id="cardPhotos"></div></section></main>
<style>.area-card-table{width:100%;border-collapse:collapse}.area-card-table th,.area-card-table td{padding:8px;text-align:left;vertical-align:top;border-bottom:1px solid #d5dfd5;white-space:pre-wrap;overflow-wrap:anywhere}.area-card-table th{width:35%}#cardMap{height:420px;z-index:0}#cardPhotos{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px}#cardPhotos img{width:100%;max-height:260px;object-fit:contain}#cardPhotos figure{margin:0}#cardPhotos figcaption{font-size:13px;overflow-wrap:anywhere}@media(max-width:600px){#cardMap{height:45dvh}.area-card-table th{width:42%}}</style>
<?php ptBoot(['area_id'=>$id,'area_fields'=>$fields,'project_name'=>$activeProjectName??($_SESSION['active_project_name']??'')]); ?><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script src="client.js?v=2.3"></script><link rel="stylesheet" href="porost_report.css?v=2.6"><script src="porost_report.js?v=2.6"></script><script src="area_card.js?v=2.13"></script>
