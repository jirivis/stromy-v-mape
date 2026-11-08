<?php require_once __DIR__.'/page_access.php';svmRequirePageLogin(); require_once __DIR__.'/common.php';if(!$canConfigure){http_response_code(403);exit('Nastavení je dostupné administrátorovi projektu.');}ptHead('Nastavení projektu'); ?>
<link rel="stylesheet" href="structures.css?v=2.8">
<main><h1><?=htmlspecialchars((string)($_SESSION['active_project_name']??'Aktivní projekt'))?></h1><p id="status" role="status" aria-live="polite"></p><p id="current"></p>
<label for="template">Použít existující strukturu jako výchozí</label><div class="tools"><select id="template"></select><button id="loadStructure">Načíst výběr polí</button></div>
<p>Pole můžete vybrat přímo zde, bez uložené struktury. Změny začnou platit po stisku Uložit nastavení projektu pro všechny uživatele projektu. Dřívější hodnoty se nemažou.</p>
<div class="tools"><button data-kind="trees">Stromy</button><button data-kind="areas">Plochy</button><button id="all">Zaškrtnout všechna pole</button><button id="minimal">Pouze základní údaje</button></div><p>Číslo objektu, stav a lokalizace zůstávají dostupné.</p><div id="fields" class="structure-fields"></div>
<div class="tools"><button id="apply">Uložit nastavení projektu</button></div>
<section class="structure-save"><h2>Uložit mezi moje struktury formulářů</h2><p>Uloží aktuální výběr polí pro stromy i plochy jako novou strukturu pro další projekty. Samotné nastavení projektu tím neuložíte.</p><label>Název struktury <input class="structure-name" id="structureName" maxlength="120"></label><button id="saveStructure">Uložit jako novou strukturu</button><p><a href="templates.php">Moje struktury formulářů</a></p></section>
<p><small>Projekt používá vlastní kopii výběru polí. Pozdější úprava uložené struktury projekt automaticky nezmění.</small></p></main>
<?php ptBoot(); ?><script src="client.js?v=2.3"></script><script src="field_picker.js?v=2.8"></script><script src="settings.js?v=2.8"></script>
