<!doctype html><html lang="cs"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="strict-origin-when-cross-origin"><title>Veřejná mapa stromů a ploch</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"><link rel="stylesheet" href="community.css?v=20261118-tabs">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"><link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<link rel="stylesheet" href="../advanced_filters.css">
<header><strong id="title">Veřejná mapa stromů a ploch</strong><label>Hledat <input id="search" type="search" placeholder="Číslo nebo veřejný údaj"></label><label><input type="checkbox" id="trees" checked> Stromy</label><label><input type="checkbox" id="areas" checked> Plochy</label><span id="status" role="status"></span><button id="deviceLocation" type="button" aria-pressed="false">Moje poloha</button><span id="locationStatus" role="status"></span><button id="toggleTable" type="button" aria-expanded="false">Tabulka</button></header>
<details id="controls"><summary id="filterSummary">Zobrazení a filtrování</summary><div class="controls">
<label>Pozadí značky <select id="symbol"><option value="white">Bílé</option><option value="transparent">Průhledné</option></select></label>
<label>Velikost podle <select id="sizeMode"><option value="girth">Obvod kmene</option><option value="dbh">Průměr kmene</option><option value="spread">Šířka koruny</option><option value="height">Výška stromu</option><option value="none">Stejná velikost</option></select></label>
<label><input id="labels" type="checkbox" checked> Čísla podle přiblížení</label>
<label>Barvení podle <select id="colorField"><option value="">Jednotné</option></select></label>
<label>Barva ploch <input id="areaColor" type="color" value="#6a852e"></label>
<label>Výplň ploch <select id="areaPattern"><option value="solid">Plná</option><option value="diagonal">Šrafování</option><option value="cross">Křížové šrafování</option></select></label>
<label>Průhlednost ploch <input id="areaTransparency" type="range" min="0" max="100" step="5" value="75"> <output id="areaTransparencyValue">75 %</output></label>
<button id="resetFilter" type="button">Zrušit všechny filtry</button>
</div><div id="legend" aria-label="Legenda barev"></div><div class="public-filter-panels"><div id="treeFilters"></div><div id="areaFilters"></div></div></details><main><div id="map" aria-label="Mapa"></div><aside id="detail" hidden><button id="close" type="button">Zavřít</button><div id="card"></div></aside></main><section id="tablePanel" hidden aria-label="Tabulka veřejných objektů"><div class="table-tabs" role="tablist" aria-label="Typ objektů"><button id="tableTrees" type="button" role="tab" aria-selected="true" aria-controls="tableContent">Stromy</button><button id="tableAreas" type="button" role="tab" aria-selected="false" aria-controls="tableContent" tabindex="-1">Plochy</button></div><div id="tableContent" role="tabpanel" aria-labelledby="tableTrees" class="table-scroll"><table><thead id="tableHead"></thead><tbody id="tableBody"></tbody></table></div><div class="pager"><button id="prev" type="button">Předchozí</button><span id="page"></span><button id="next" type="button">Další</button></div></section>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script><script>window.TreeApp={};</script><script src="../js/markers.js?v=2.25-public"></script><script src="../js/project_filters.js?v=20261118-tabs"></script><script src="community.js?v=20261118-tabs"></script></html>




