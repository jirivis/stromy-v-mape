<?php require_once __DIR__.'/page_access.php';svmRequirePageLogin(); require_once __DIR__.'/common.php';ptHead('Tabulka projektu'); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="../advanced_filters.css?v=2.24.1">
<main><div class="tools"><button data-kind="trees" aria-pressed="true">Stromy</button><button data-kind="areas" aria-pressed="false">Plochy</button><button id="resetOrder" title="Vrátit výchozí pořadí sloupců">Výchozí pořadí sloupců</button><button id="save">Uložit změny</button></div>
<div class="tools"><button id="selectAll">Vybrat vše</button><button id="invertSelection">Invertovat výběr</button><button id="export">Export vybraných</button><button id="overview">Přehledka vybraných</button><button id="cards">Karty vybraných</button><button id="deleteSelected" class="danger">Smazat vybrané</button><span id="selectionCount"></span></div>
<details id="filterPanel"><summary>Filtrování mapy a tabulky</summary><div id="sharedFilters"></div></details>
<p id="status" role="status"></p><p class="table-help">Výběr platí pro všechny právě zobrazené výsledky, i na dalších stránkách. <span class="missing-legend">Modře: bez polohy.</span></p>
<div class="scroll"><table class="project-table"><thead id="head"></thead><tbody id="rows"></tbody></table></div><div class="tools"><button id="prev">Předchozí</button><span id="page"></span><button id="next">Další</button></div></main>
<div id="rowMenu" class="row-menu" hidden><button id="showMap">Zobrazit na rychlé mapě</button><a id="editRow" target="_blank" rel="noopener">Upravit ve formuláři</a></div>
<dialog id="quickDialog" class="quick-dialog" aria-labelledby="quickTitle"><div class="tools"><strong id="quickTitle">Rychlá mapa</strong><button id="closeMap">Zavřít</button></div><div id="quickMap"></div></dialog>
<?php ptBoot(); ?><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script src="client.js?v=2.3"></script><script src="../js/project_filters.js?v=2.24.1"></script><script src="table.js?v=2.24.1"></script>
