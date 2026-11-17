<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__.'/app/project_directory.php';
require_once __DIR__.'/app/auth_form.php';
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

$userId =
    (int) ($_SESSION['user_id'] ?? 0);

$role =
    $_SESSION['role']
    ?? 'viewer';

$isSuperAdmin =
    (int) (
        $_SESSION['is_super_admin']
        ?? 0
    );

/*
 * Nové projekty může vytvářet
 * superadmin nebo globální admin.
 */
if (
    $isSuperAdmin !== 1
    && $role !== 'admin'
) {
    http_response_code(403);

    die(
        'Nemáte oprávnění vytvářet projekty.'
    );
}


/*
---------------------------------------------------
 Výchozí hodnoty formuláře
---------------------------------------------------
*/

$error = '';

require_once __DIR__.'/map/public/project_tools/structure_catalog.php';
$canChangeOwner = $isSuperAdmin === 1;
$_SESSION['project_new_csrf'] ??= bin2hex(random_bytes(32));
$structureHidden=['trees'=>[],'areas'=>[]];
$structureName='Vlastní výběr polí';
$structures=[];$structureCatalog=[];$structureAvailable=true;
try {
    $structureCatalog=ptCatalog();
    $structureStmt=$mysqli->prepare('SELECT name,fields_json FROM svm_form_templates WHERE owner_user_id=? ORDER BY name');
    if(!$structureStmt)throw new RuntimeException('structures');
    $structureStmt->bind_param('i',$userId);if(!$structureStmt->execute())throw new RuntimeException('structures');
    foreach($structureStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row){
        $structures[]=['name'=>$row['name'],'hidden'=>ptHidden(json_decode($row['fields_json'],true,64,JSON_THROW_ON_ERROR))];
    }
    $structureStmt->close();
    if(isset($_SESSION['project_new_draft']['structure_hidden'])){
        $structureHidden=ptHidden($_SESSION['project_new_draft']['structure_hidden']);
        $structureName=(string)($_SESSION['project_new_draft']['structure_name']??'Vlastní výběr polí');
    }
}catch(Throwable $e){$structureAvailable=false;$error='Struktury formulářů se nepodařilo načíst. Projekt zatím nebyl vytvořen.';}


$projectDraft =
    $_SESSION['project_new_draft']
    ?? [];

$name =
    (string) (
        $projectDraft['name']
        ?? ''
    );

$clientId =
    isset($_GET['client_id'])
        ? (int) $_GET['client_id']
        : (int) (
            $projectDraft['client_id']
            ?? 0
        );

$visibility =
    (string) (
        $projectDraft['visibility']
        ?? 'private'
    );

$ownerId =
    (int) (
        $projectDraft['owner_id']
        ?? $userId
    );


/*
---------------------------------------------------
 Načtení zákazníků
---------------------------------------------------
*/

if (!$canChangeOwner) $ownerId=$userId;

$clients = svmProjectClients($mysqli,$userId,$isSuperAdmin === 1);


/*
---------------------------------------------------
 Načtení aktivních uživatelů
 pro volbu vlastníka projektu
---------------------------------------------------
*/

$users = [];
$ownerQuery=$mysqli->prepare('SELECT id,username,email FROM users WHERE active=1'.($canChangeOwner?'':' AND id=?').' ORDER BY username,id');
if(!$canChangeOwner){$visibleOwner=$userId;$ownerQuery->bind_param('i',$visibleOwner);}
$ownerQuery->execute();$users=$ownerQuery->get_result()->fetch_all(MYSQLI_ASSOC);$ownerQuery->close();


/*
---------------------------------------------------
 Uložení nového projektu
---------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    try {
        if(!hash_equals($_SESSION['project_new_csrf'],(string)($_POST['project_new_csrf']??'')))throw new RuntimeException('Platnost formuláře vypršela. Obnovte stránku.');
        if(!$structureAvailable)throw new RuntimeException($error);
        if(($_POST['structure_ready']??'')!=='1')throw new RuntimeException('Vyčkejte na načtení výběru polí a zkuste to znovu.');
        $structureHidden=ptHidden(json_decode((string)($_POST['structure_hidden']??''),true,64,JSON_THROW_ON_ERROR));
        $structureName=trim((string)($_POST['structure_name']??'Vlastní výběr polí'));
        if($structureName===''||mb_strlen($structureName)>120)throw new RuntimeException('Neplatný název struktury.');
    }catch(Throwable $e){$error=$e instanceof JsonException?'Neplatný výběr polí.':$e->getMessage();}
    if($error===''){
    $name =
        trim(
            $_POST['name']
            ?? ''
        );

    $clientId =
        (int) (
            $_POST['client_id']
            ?? 0
        );

    $visibility =
        $_POST['visibility']
        ?? 'private';

    $ownerId =
        (int) (
            $_POST['owner_id']
            ?? $userId
        );
		
if (!$canChangeOwner) $ownerId=$userId;

$action =
    $_POST['action']
    ?? 'create_project';


/*
 * Uložení rozepsaného projektu
 * před přechodem na založení zákazníka.
 */
if ($action === 'new_client') {
    if ($isSuperAdmin !== 1) { http_response_code(403); exit('Zákazníky zakládá správce aplikace.'); }

    $_SESSION['project_new_draft'] = [
        'name' =>
            $name,

        'client_id' =>
            $clientId,

        'visibility' =>
            $visibility,

        'owner_id' =>
            $ownerId,
        'structure_hidden'=>$structureHidden,
        'structure_name'=>$structureName
    ];

    header(
        'Location: /client_new.php'
        . '?return_to=project_new'
    );

    exit;
}		


    /*
     * Název projektu je povinný.
     */
    if ($name === '') {

        $error =
            'Zadejte název projektu.';
    }


    /*
     * Povolené hodnoty viditelnosti.
     */
    if (
        !in_array(
            $visibility,
            [
                'private',
                'public'
            ],
            true
        )
    ) {
        $visibility =
            'private';
    }


    if ($clientId > 0 && !in_array($clientId,array_map('intval',array_column($clients,'id')),true)) {
        $error='K vybranému zadavateli nemáte přístup.';
    }

    /*
     * Ověření zákazníka.
     *
     * Hodnota 0 znamená,
     * že projekt nebude mít zadavatele.
     */
    if (
        $error === ''
        && $clientId > 0
    ) {

        $clientStmt =
            $mysqli->prepare(
                'SELECT id
                 FROM clients
                 WHERE id = ?
                 LIMIT 1'
            );

        if (!$clientStmt) {

            $error =
                'Zadavatele se nepodařilo ověřit.';

        } else {

            $clientStmt->bind_param(
                'i',
                $clientId
            );

            $clientStmt->execute();

            $clientCheck =
                $clientStmt
                    ->get_result()
                    ->fetch_assoc();

            $clientStmt->close();

            if (!$clientCheck) {

                $error =
                    'Vybraný zadavatel neexistuje.';
            }
        }
    }


    /*
     * Ověření vlastníka projektu.
     */
    if ($error === '') {

        $ownerStmt =
            $mysqli->prepare(
                'SELECT id
                 FROM users
                 WHERE id = ?
                   AND active = 1
                 LIMIT 1'
            );

        if (!$ownerStmt) {

            $error =
                'Vlastníka projektu se nepodařilo ověřit.';

        } else {

            $ownerStmt->bind_param(
                'i',
                $ownerId
            );

            $ownerStmt->execute();

            $ownerCheck =
                $ownerStmt
                    ->get_result()
                    ->fetch_assoc();

            $ownerStmt->close();

            if (!$ownerCheck) {

                $error =
                    'Vybraný vlastník neexistuje '
                    . 'nebo není aktivní.';
            }
        }
    }


    /*
---------------------------------------------------
 Vytvoření projektu
---------------------------------------------------
*/

    if ($error === '') {

        /*
         * Pokud není zadavatel vybraný,
         * uložíme NULL.
         */
        $clientIdForDb =
            $clientId > 0
                ? $clientId
                : null;

        $mysqli->begin_transaction();

        try {

            $projectStmt =
                $mysqli->prepare(
                    'INSERT INTO projects (
                        name,
                        client_id,
                        owner_id,
                        visibility
                     )
                     VALUES (?, ?, ?, ?)'
                );

            if (!$projectStmt) {

                throw new RuntimeException(
                    $mysqli->error
                );
            }

            $projectStmt->bind_param(
                'siis',
                $name,
                $clientIdForDb,
                $ownerId,
                $visibility
            );

            if (
                !$projectStmt->execute()
            ) {

                throw new RuntimeException(
                    $projectStmt->error
                );
            }

            $projectId =
                (int) $projectStmt
                    ->insert_id;

            $projectStmt->close();


            /*
             * Vlastník projektu se současně
             * přidá do project_users
             * jako owner.
             */
            $projectUserStmt =
                $mysqli->prepare(
                    'INSERT INTO project_users (
                        project_id,
                        user_id,
                        project_role
                     )
                     VALUES (?, ?, "owner")
                     ON DUPLICATE KEY UPDATE
                        project_role = "owner"'
                );

            if (!$projectUserStmt) {

                throw new RuntimeException(
                    $mysqli->error
                );
            }

            $projectUserStmt->bind_param(
                'ii',
                $projectId,
                $ownerId
            );

            if (
                !$projectUserStmt->execute()
            ) {

                throw new RuntimeException(
                    $projectUserStmt->error
                );
            }

            $projectUserStmt->close();


            /*
             * Všechno proběhlo správně.
             */
            $structureJson=json_encode(['hidden'=>$structureHidden,'profiles'=>new stdClass(),'template_name'=>$structureName],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $settingsStmt=$mysqli->prepare('INSERT INTO svm_form_settings(project_id,settings_json) VALUES(?,?)');
            if(!$settingsStmt)throw new RuntimeException('settings');
            $settingsStmt->bind_param('is',$projectId,$structureJson);
            if(!$settingsStmt->execute())throw new RuntimeException('settings');
            $settingsStmt->close();
            if(!$mysqli->commit())throw new RuntimeException('commit');


            /*
             * Aktivujeme nově vytvořený projekt.
             */
            $_SESSION['active_project_id'] =
                $projectId;

            $_SESSION['active_project_name'] =
                $name;

            $_SESSION['active_project_role'] =
                $ownerId === $userId ? 'owner' : ($isSuperAdmin === 1 ? 'admin' : 'viewer');

			/*
			 * Projekt už byl vytvořen,
			 * rozepsaný koncept nepotřebujeme.
			 */
			unset(
				$_SESSION['project_new_draft']
			);

            header(
                'Location: /projects.php'
            );

            exit;

        } catch (Throwable $exception) {

            $mysqli->rollback();

            error_log(
                'project_new.php: '
                . $exception->getMessage()
            );

            $error =
                'Projekt se nepodařilo vytvořit.';
        }
    }
}


}

/*
---------------------------------------------------
 Vzhled stránky
---------------------------------------------------
*/

$pageTitle =
    'Nový projekt';

$pageSubtitle =
    'Správa projektů';

$cardClass =
    'admin-card admin-card--wide';

require_once __DIR__
    . '/app/includes/header.php';

?>


<?php if ($error !== ''): ?>

    <div
        class="admin-message admin-message--error"
    >
        <?= htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>

<?php endif; ?>


<h2>
    Nový projekt
</h2>


<form
    method="post"
    class="admin-form"
>
<input type="hidden" name="auth_form_token" value="<?= htmlspecialchars($_SESSION['auth_form_token'],ENT_QUOTES,'UTF-8') ?>">

    <div class="admin-field">

        <label for="name">
            Název projektu
        </label>

        <input
            type="text"
            id="name"
            name="name"
            maxlength="100"
            required
            value="<?= htmlspecialchars(
                $name,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

    </div>


    <div class="admin-field">

        <label for="client_id">
            Zadavatel
        </label>

        <select
            id="client_id"
            name="client_id"
        >

            <option value="0">
                — bez zadavatele —
            </option>

            <?php foreach (
                $clients
                as $client
            ): ?>

                <option
                    value="<?= (int) $client['id'] ?>"
                    <?= (
                        (int) $client['id']
                        === $clientId
                    )
                        ? 'selected'
                        : ''
                    ?>
                >
                    <?= htmlspecialchars(
                        $client['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    <?php if (
                        !empty(
                            $client['ico']
                        )
                    ): ?>

                        – IČO
                        <?= htmlspecialchars(
                            $client['ico'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    <?php endif; ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>


<?php if ($isSuperAdmin === 1): ?>
<div class="admin-field-help">

    <button
        type="submit"
        name="action"
        value="new_client"
        class="admin-link-button"
        formnovalidate
    >
        + Vytvořit nového zákazníka
    </button>

</div>
<?php endif; ?>


    <div class="admin-field">

        <label for="visibility">
            Viditelnost
        </label>

        <select
            id="visibility"
            name="visibility"
        >

            <option
                value="private"
                <?= $visibility === 'private'
                    ? 'selected'
                    : ''
                ?>
            >
                Soukromý
            </option>

            <option
                value="public"
                <?= $visibility === 'public'
                    ? 'selected'
                    : ''
                ?>
            >
                Veřejný
            </option>

        </select>

    </div>


<div class="admin-field">

    <label for="owner_id">
        Vlastník projektu
    </label>

    <?php if ($canChangeOwner): ?>

        <select
            id="owner_id"
            name="owner_id"
            required
        >

            <?php foreach (
                $users
                as $user
            ): ?>

                <option
                    value="<?= (int) $user['id'] ?>"
                    <?= (
                        (int) $user['id']
                        === $ownerId
                    )
                        ? 'selected'
                        : ''
                    ?>
                >
                    <?= htmlspecialchars(
                        $user['username'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    <?php if (
                        !empty(
                            $user['email']
                        )
                    ): ?>

                        –
                        <?= htmlspecialchars(
                            $user['email'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    <?php endif; ?>

                </option>

            <?php endforeach; ?>

        </select>

    <?php else: ?>

        <?php

        $ownerDisplay =
            'Uživatel #' . $ownerId;

        foreach ($users as $user) {

            if (
                (int) $user['id']
                === $ownerId
            ) {

                $ownerDisplay =
                    (string) $user['username'];

                break;
            }
        }

        ?>

        <input
            type="text"
            value="<?= htmlspecialchars(
                $ownerDisplay,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            readonly
        >

    <?php endif; ?>

</div>


    <section aria-labelledby="structureHeading">
        <h2 id="structureHeading">Struktura formulářů</h2>
        <p>Vyberte pole pro tento projekt nebo načtěte svoji uloženou strukturu. Výběr se uloží společně s projektem a bude platit pro všechny jeho uživatele.</p>
        <label for="structureSelect">Existující struktura</label>
        <div class="structure-tools"><select id="structureSelect"></select><button id="loadStructure" type="button">Načíst výběr polí</button></div>
        <div class="structure-tools"><button type="button" data-kind="trees">Stromy</button><button type="button" data-kind="areas">Plochy</button><button id="allFields" type="button">Zaškrtnout všechna pole</button><button id="minimalFields" type="button">Pouze základní údaje</button></div>
        <p>Číslo objektu, stav a lokalizace zůstávají dostupné. Pole můžete po založení změnit v Nastavení projektu a tam také uložit výběr jako novou strukturu.</p>
        <div id="structureFields" class="structure-fields"></div><p id="structureStatus" role="status" aria-live="polite"></p>
        <noscript>Pro výběr struktury a založení projektu zapněte JavaScript.</noscript>
        <input type="hidden" name="project_new_csrf" value="<?=htmlspecialchars($_SESSION['project_new_csrf'],ENT_QUOTES,'UTF-8')?>">
        <input type="hidden" name="structure_ready" value="0">
        <input type="hidden" name="structure_hidden" id="structureHidden" value="">
        <input type="hidden" name="structure_name" id="structureName" value="">
    </section>

    <div class="admin-actions">

<button
    type="submit"
    name="action"
    value="create_project"
    class="btn"
>
    Vytvořit projekt
</button>

        <a
            href="/projects.php"
            class="btn btn-secondary"
        >
            Zrušit
        </a>

    </div>

</form>
<link rel="stylesheet" href="/map/public/project_tools/structures.css?v=2.8">
<style>.structure-tools{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}.structure-tools button{padding:8px 12px;min-height:40px;border:1px solid #a1b3a3;border-radius:6px;background:#eef5ef;color:#245b37}.structure-tools button[aria-pressed=true]{background:#28613d;color:white}.structure-fields input[type=checkbox]{width:auto!important}.structure-fields fieldset{padding:12px;border:1px solid #cad7cd;border-radius:6px}</style>
<script>window.NewProjectStructure=<?=json_encode(['catalog'=>$structureCatalog,'structures'=>$structures,'hidden'=>$structureHidden,'name'=>$structureName,'available'=>$structureAvailable],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR)?>;</script>
<script src="/map/public/project_tools/field_picker.js?v=2.8"></script>
<script src="/map/public/project_tools/project_new_structure.js?v=2.8.1"></script>



<?php

require_once __DIR__
    . '/app/includes/footer.php';

?>