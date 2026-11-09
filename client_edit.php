<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__.'/app/auth_form.php';
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

$role = $_SESSION['role'] ?? 'viewer';
$isSuperAdmin =
    (int) ($_SESSION['is_super_admin'] ?? 0);

if (
    $isSuperAdmin !== 1
    && $role !== 'admin'
) {
    http_response_code(403);

    die(
        'Nemáte oprávnění upravovat zákazníky.'
    );
}


$clientId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$clientId || $clientId < 1) {
    http_response_code(400);
    die('Chybí platné ID zákazníka.');
}


/*
 * Načtení zákazníka.
 */
$stmt = $mysqli->prepare(
    'SELECT
        id,
        name,
        ico,
        address,
        email,
        phone,
        note,
        created_at
     FROM clients
     WHERE id = ?
     LIMIT 1'
);

if (!$stmt) {
    http_response_code(500);
    die('Zákazníka se nepodařilo načíst.');
}

$stmt->bind_param(
    'i',
    $clientId
);

$stmt->execute();

$result =
    $stmt->get_result();

$client =
    $result->fetch_assoc();

$stmt->close();

if (!$client) {
    http_response_code(404);
    die('Zákazník nebyl nalezen.');
}


/*
 * Výchozí hodnoty formuláře.
 */
$name =
    (string) ($client['name'] ?? '');

$ico =
    (string) ($client['ico'] ?? '');

$address =
    (string) ($client['address'] ?? '');

$email =
    (string) ($client['email'] ?? '');

$phone =
    (string) ($client['phone'] ?? '');

$note =
    (string) ($client['note'] ?? '');

$error = '';
$success = '';


/*
 * Uložení změn.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name =
        trim(
            $_POST['name']
            ?? ''
        );

    $ico =
        trim(
            $_POST['ico']
            ?? ''
        );

    $address =
        trim(
            $_POST['address']
            ?? ''
        );

    $email =
        trim(
            $_POST['email']
            ?? ''
        );

    $phone =
        trim(
            $_POST['phone']
            ?? ''
        );

    $note =
        trim(
            $_POST['note']
            ?? ''
        );


    if ($name === '') {

        $error =
            'Zadejte název zákazníka.';

    } elseif (
        $email !== ''
        && !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Zadaný e-mail není platný.';
    }


    if ($error === '') {

        $updateStmt =
            $mysqli->prepare(
                'UPDATE clients
                 SET
                    name = ?,
                    ico = ?,
                    address = ?,
                    email = ?,
                    phone = ?,
                    note = ?
                 WHERE id = ?
                 LIMIT 1'
            );

        if (!$updateStmt) {

            $error =
                'Změny se nepodařilo připravit.';

        } else {

            $updateStmt->bind_param(
                'ssssssi',
                $name,
                $ico,
                $address,
                $email,
                $phone,
                $note,
                $clientId
            );

            if ($updateStmt->execute()) {

                $success =
                    'Údaje zákazníka byly uloženy.';

            } else {

                $error =
                    'Údaje zákazníka se nepodařilo uložit.';
            }

            $updateStmt->close();
        }
    }
}


/*
 * Projekty tohoto zákazníka.
 */
$projects = [];

$projectStmt =
    $mysqli->prepare(
        'SELECT
            p.id,
            p.name,
            p.visibility,
            COUNT(t.id) AS tree_count
         FROM projects p
         LEFT JOIN trees t
            ON t.project_id = p.id
         WHERE p.client_id = ?
         GROUP BY
            p.id,
            p.name,
            p.visibility
         ORDER BY
            p.name,
            p.id'
    );

if ($projectStmt) {

    $projectStmt->bind_param(
        'i',
        $clientId
    );

    $projectStmt->execute();

    $projectResult =
        $projectStmt->get_result();

    while (
        $project =
            $projectResult->fetch_assoc()
    ) {
        $projects[] = $project;
    }

    $projectStmt->close();
}


$pageTitle =
    'Upravit zákazníka';

$pageSubtitle =
    'Správa zákazníků';

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


<?php if ($success !== ''): ?>

    <div
        class="admin-message admin-message--success"
    >
        <?= htmlspecialchars(
            $success,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>

<?php endif; ?>


<h2>
    Upravit zákazníka
</h2>


<form
    method="post"
    class="admin-form"
>
<input type="hidden" name="auth_form_token" value="<?= htmlspecialchars($_SESSION['auth_form_token'],ENT_QUOTES,'UTF-8') ?>">

    <div class="admin-field">

        <label for="name">
            Název subjektu
        </label>

        <input
            type="text"
            id="name"
            name="name"
            maxlength="255"
            required
            value="<?= htmlspecialchars(
                $name,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

    </div>


    <div class="admin-field">

        <label for="ico">
            IČO
        </label>

        <input
            type="text"
            id="ico"
            name="ico"
            maxlength="20"
            value="<?= htmlspecialchars(
                $ico,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

    </div>


    <div class="admin-field">

        <label for="address">
            Adresa
        </label>

        <textarea
            id="address"
            name="address"
            rows="3"
        ><?= htmlspecialchars(
            $address,
            ENT_QUOTES,
            'UTF-8'
        ) ?></textarea>

    </div>


    <div class="admin-field">

        <label for="email">
            E-mail
        </label>

        <input
            type="email"
            id="email"
            name="email"
            maxlength="255"
            value="<?= htmlspecialchars(
                $email,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

    </div>


    <div class="admin-field">

        <label for="phone">
            Telefon
        </label>

        <input
            type="text"
            id="phone"
            name="phone"
            maxlength="50"
            value="<?= htmlspecialchars(
                $phone,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

    </div>


    <div class="admin-field">

        <label for="note">
            Poznámka
        </label>

        <textarea
            id="note"
            name="note"
            rows="4"
        ><?= htmlspecialchars(
            $note,
            ENT_QUOTES,
            'UTF-8'
        ) ?></textarea>

    </div>


    <div class="admin-actions">

        <button
            type="submit"
            class="btn"
        >
            Uložit změny
        </button>

        <a
            href="/clients.php"
            class="btn btn-secondary"
        >
            Zpět
        </a>

    </div>

</form>


<?php if ($projects): ?>

    <hr>

    <h2>
        Projekty zákazníka
    </h2>

    <div class="project-list">

        <?php foreach ($projects as $project): ?>

            <article class="project-card">

                <div class="project-card__header">

                    <div>

                        <h3>
                            <?= htmlspecialchars(
                                $project['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>

                    </div>

                    <span
                        class="
                            project-card__visibility
                            project-card__visibility--<?= htmlspecialchars(
                                $project['visibility'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        "
                    >
                        <?= $project['visibility']
                            === 'public'
                                ? 'Veřejný'
                                : 'Soukromý'
                        ?>
                    </span>

                </div>


                <div class="project-card__stats">

                    <div class="project-stat">

                        <strong>
                            <?= (int) $project['tree_count'] ?>
                        </strong>

                        <span>
                            stromů
                        </span>

                    </div>

                </div>


                <div class="project-card__actions">

                    <a
                        href="/project_edit.php?id=<?= (int) $project['id'] ?>"
                        class="btn btn-secondary"
                    >
                        Upravit projekt
                    </a>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<?php

require_once __DIR__
    . '/app/includes/footer.php';

?>