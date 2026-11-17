<?php
require_once __DIR__.'/page_access.php';svmRequirePageLogin();require_once __DIR__.'/common.php';requireProjectRole(['owner','admin','editor']);if(!$canEdit){http_response_code(403);exit('Nemáte právo importovat.');}ptHead('Import fotografií');
?>
<main><?php require_once __DIR__.'/transfer_nav.php';ptTransferNav('import'); ?>
<h1>Import fotografií k existujícím objektům</h1>
<p>Fotografie se přidají do galerie. Existující fotografie ani údaje objektů se nepřepisují. Nejprve musí být v projektu vytvořené stromy nebo plochy.</p>
<fieldset id="photoInputs"><legend>1. Fotografie a přiřazení</legend>
<label>Typ objektů <select id="photoKind"><option value="trees">Stromy</option><option value="areas">Plochy</option></select></label>
<label>Vybrat složku (včetně podsložek) <input id="photoFolder" type="file" webkitdirectory multiple></label>
<label>Nebo vybrat jednotlivé fotografie <input id="photoFiles" type="file" multiple accept="image/jpeg,image/png,image/webp"></label>
<label>Způsob přiřazení <select id="photoMode"><option value="folders">Podle podsložek: strom66 / Strom_01 / 66 nebo plocha66 / 66</option><option value="single">Všechny vybrané fotografie k jednomu objektu</option><option value="csv">Podle přiřazovací tabulky CSV (UTF-8)</option></select></label>
<label>Číslo objektu pro jednu složku <input id="photoCode" type="text"></label>
<label>Přiřazovací tabulka <input id="photoCsv" type="file" accept=".csv,.txt"></label>
<p><strong>Každá fotografie musí mít v přiřazovací tabulce vlastní řádek.</strong> Sloupce: <code>soubor;cislo;popisek</code>. Soubor je název fotografie nebo její relativní cesta v podsložkách. Při opakovaných názvech je nutná celá cesta. Soubor může být přiřazen jen jednou v jedné dávce.</p>
<p>Například: <code>strom66/20261112_153000.jpg;66;Pohled na korunu</code>. Nový export přikládá rovnou CSV přiřazení zvlášť pro stromy a plochy. Starší XLSX přiřazení nejprve v Excelu uložte jako CSV UTF-8 a přiřaďte sloupce na názvy výše.</p>
<p>Nejvýše 500 fotografií v dávce, každá JPG, PNG nebo WebP do 5 MB a 24 megapixelů. Fotografie nad limitem a nerozpoznané složky se zobrazí jako přeskočené. Další fotografie můžete před potvrzením odškrtnout. Nejednoznačné a nenalezené objekty se neimportují.</p>
<button id="photoPreview" type="button">Připravit kontrolní náhled</button></fieldset>
<p id="photoStatus" role="status"></p><div class="scroll"><table><thead><tr><th>Importovat</th><th>Soubor</th><th>Číslo objektu</th><th>Popisek</th><th>Stav</th></tr></thead><tbody id="photoRows"></tbody></table></div>
<button id="photoCommit" type="button" disabled>Potvrdit import fotografií</button><p>Fotografie se nahrávají postupně. Při přerušení zůstávají úspěšně uložené fotografie zachované; během této stránky lze pokračovat bez jejich opakování.</p><nav class="tools no-print" aria-label="Návrat do přehledu"><a class="button" href="/dashboard.php" target="_top">← Dashboard</a></nav></main>
<?php ptBoot(); ?><script src="import_photos.js?v=2.24.3"></script>
