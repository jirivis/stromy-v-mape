<?php
declare(strict_types=1);
require_once __DIR__ . '/../project_tools/page_access.php';
svmRequirePageLogin();
require_once __DIR__.'/../../../auth_api.php';
// auth_api sets JSON headers; this endpoint renders a page.
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
if(empty($_SESSION['areas_csrf'])) $_SESSION['areas_csrf']=bin2hex(random_bytes(32));
require_once __DIR__.'/../../../app/form_behavior.php';
$afterSave=svmProjectFormBehavior($mysqli,(int)$activeProjectId)['areas'];
$boot=['after_save'=>$afterSave,'csrf'=>$_SESSION['areas_csrf'],'project_id'=>(int)$activeProjectId,'can_edit'=>in_array($activeProjectRole??'', ['owner','admin','editor'],true),'project_name'=>$_SESSION['active_project_name']??'Projekt'];
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Plochy porostů – Stromy v mapě</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
<link rel="stylesheet" href="areas.css?v=20261125-help">
</head><body>
<header><a href="/dashboard.php" target="_top">← Dashboard</a><a id="backToMap" href="/map/public/index.php">← Hlavní mapa</a><strong id="pageMode"><?=empty($_GET['new'])&&!empty($_GET['id'])?'Editace plochy':'Přidání plochy'?></strong><button id="closeEditor" type="button">Zavřít · Esc</button><a id="areaCardLink" hidden target="_blank" rel="noopener">Karta porostu ↗</a><span id="projectName"></span></header>
<div id="status" role="status" aria-live="polite">Načítám plochy…</div>
<main><section class="map-column" aria-label="Mapa porostů">
<button id="newArea" type="button" hidden>Nová plocha</button><button id="allAreas" type="button" hidden>Zobrazit všechny</button>
<div class="tools boundary-start"><button type="button" id="drawArea">Nakreslit hranici</button><button type="button" id="editArea">Upravit hranici</button></div>
<div id="boundaryTools" hidden>
<div class="tools"><label>Zadávání vrcholů <select id="vertexMethod"><option value="mouse">Kliknutím / tažením v mapě</option><option value="cross">Křížem uprostřed mapy</option><option value="gnss">Aktuální polohou GNSS</option></select></label><button type="button" id="applyVertex" hidden>Přidat vrchol</button></div>
<div class="tools"><button type="button" id="clearVertex" hidden>Zrušit výběr vrcholu / hrany</button><button type="button" id="removeVertex" hidden>Odebrat vybraný vrchol</button><button type="button" id="undoVertex" title="Krok zpět" aria-label="Krok zpět">↶</button><button type="button" id="redoVertex" title="Krok vpřed" aria-label="Krok vpřed">↷</button><button type="button" id="finishVertices" class="primary">Dokončit hranici</button><button type="button" id="cancelBoundary">Zrušit úpravy hranice</button></div>

</div>
<label class="snap-option"><input id="snapAreas" type="checkbox" checked> Přichytávat k plochám </label>
<div id="areaMap"></div><div id="boundaryBelow"></div><p class="hint boundary-validity">Obrys jedné plochy se nesmí křížit ani dotýkat sám sebe. Takovou hranici nelze potvrdit a plochu s ní nelze uložit.</p><div class="boundary-status" role="status"><span id="boundaryHint">Spusťte kreslení nebo úpravu hranice.</span> <span id="gnssInfo"></span></div><select id="areaList" hidden><option value="">— Vyberte plochu —</option></select></section>
<section class="form-column"><h1 id="formTitle"><?=empty($_GET['new'])&&!empty($_GET['id'])?'Načítám plochu k editaci…':'Nová plocha'?></h1>
<form id="areaForm"><fieldset id="fields">
<h2>Identifikace</h2><label>Číslo plochy<input name="code" required maxlength="40" placeholder="1"></label><label>Název<input name="name" maxlength="200"></label>
<input type="hidden" name="status" value="existing">
<h2>Výměra</h2><p>Výměra z mapy: <strong id="mapArea">—</strong> m²</p>
<p class="hint">Výměra z hranice je orientační. Změna hranice nepřepíše výměry zadané níže pro AOPK.</p>
<h2>Druhové složení</h2><div id="speciesRows"></div><button type="button" id="addSpecies">＋ Přidat druh</button><p class="hint">Vyhledávejte česky nebo latinsky. Zastoupení je nepovinné; součet zadaných podílů může být nejvýše 100 %.</p>
<div class="two"><label>Výška od (m)<input name="height_min" type="number" min="0" max="150" step="0.1"></label><label>Výška do (m)<input name="height_max" type="number" min="0" max="150" step="0.1"></label></div>
<h2>Podklady pro AOPK 2022</h2><p class="hint">Zvolte charakter a zadejte jeho výměru. Výrazně odlišné části evidujte odděleně. Ocenění v Kč zatím není součástí této verze.</p>
<div id="componentRows"></div><button type="button" id="addComponent">＋ Přidat charakter porostu</button><p id="componentTotal"></p><div id="aopkFields"></div>
<p class="hint">Výklad možností je stručným shrnutím metodiky. <a href="https://ocenovanidrevin.nature.cz/metodika.pdf#page=28" target="_blank" rel="noopener">Metodika AOPK ČR 2022, kapitola 4 ↗</a></p>
<h2>Péče a poznámky</h2><label>Popis stavu<textarea name="state_note" maxlength="5000"></textarea></label><label>Navržené zásahy<textarea name="treatments" maxlength="5000"></textarea></label><label>Naléhavost<select name="urgency"><option value="">Nehodnoceno</option><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option></select></label><label>Poznámka<textarea name="note" maxlength="5000"></textarea></label>
<h2>Časové údaje</h2><div class="two"><label>Datum výsadby<input name="planted" type="date"></label><label>Datum měření<input name="measured_at" type="date"></label><label>Poslední kontrola<input name="last_insp_at" type="date"></label><label>Další kontrola<input name="next_insp_at" type="date"></label></div>
<section id="areaLocation"><h2>Lokalizace a hranice</h2></section>
<div class="save-actions"><button class="primary" id="save" type="submit">Uložit plochu</button><button type="submit" form="areaForm" id="saveHistory" hidden>Uložit záznam do historie</button></div>
</fieldset></form>
<section id="areaPhotos"><h2>Fotografie</h2><p id="photoHelp">Nejdříve uložte plochu, potom přidejte fotografie.</p><div class="tools"><button type="button" id="pickPhotos" disabled>Nahrát foto</button><button type="button" id="takePhoto" disabled>Fotit</button></div><input id="cameraFile" type="file" accept="image/*" capture="environment" hidden disabled><input id="photoFiles" type="file" accept="image/jpeg,image/png,image/webp" multiple hidden disabled><p id="photoSelection"></p><div id="photos"></div></section>
<section class="area-state-actions"><h2>Stav porostu</h2>
<button type="button" id="removedArea" hidden>Označit porost jako odstraněný</button><p id="removedHint"></p>
<button type="button" id="deleteArea" class="danger" hidden>Smazat plochu</button>
</section>
<details id="areaHistory"><summary>Historické záznamy</summary><p>Historie hodnot a hranic. Běžné uložení zaznamenává změny automaticky; tlačítkem „Uložit záznam do historie“ označíte aktuální stav jako samostatný historický záznam.</p><p class="hint">Uloží také aktuální změny formuláře. Fotografie nejsou součástí historického snímku.</p><div id="historyRows"></div><button type="button" id="moreHistory" hidden>Starší záznamy</button></details>
<div class="editor-close-bottom"><button id="closeEditorBottom" type="button">Zavřít · Esc</button></div>
</section></main>
<script>window.AreasBoot=<?= json_encode($boot,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script><script src="../js/project_context_map.js?v=20261125"></script><script src="boundary_editor.js?v=20261125"></script><script src="areas.js?v=20261125-help"></script>
<script>window.FormFieldsBoot={kind:'areas',project_id:<?= (int)$activeProjectId ?>};</script><script src="../project_tools/visibility.js?v=2.3"></script></body></html>


