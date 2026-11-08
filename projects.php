<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

$username = $_SESSION['username'] ?? 'Uživatel';
$role = $_SESSION['role'] ?? 'viewer';
$isSuperAdmin =
    (int) ($_SESSION['is_super_admin'] ?? 0);

$userId =
    (int) ($_SESSION['user_id'] ?? 0);

$pageTitle = 'Projekty';
$pageSubtitle = 'Správa projektů';
$cardClass = 'admin-card admin-card--wide';


/*
 * K projektům mají na tuto stránku přístup:
 * - superadmin,
 * - globální admin,
 * - případně uživatelé s projekty,
 *   které vlastní nebo spravují.
 */
if ($userId < 1) {
    header('Location: /login.php');
    exit;
}


/*
 * Načtení projektů.
 *
 * Superadmin vidí všechny.
 * Ostatní jen ty, ke kterým mají přístup.
 */
if ($isSuperAdmin === 1) {

    $stmt = $mysqli->prepare(
        'SELECT
            p.id,
            p.name,
            p.owner_id,
            p.visibility,
            p.created_at,

            u.username AS owner_username,

            COUNT(DISTINCT t.id)
                AS tree_count,

            COUNT(DISTINCT pu.user_id)
                AS user_count,

            "admin" AS project_role

         FROM projects p

         LEFT JOIN users u
            ON u.id = p.owner_id

         LEFT JOIN trees t
            ON t.project_id = p.id

         LEFT JOIN project_users pu
            ON pu.project_id = p.id

         GROUP BY
            p.id,
            p.name,
            p.owner_id,
            p.visibility,
            p.created_at,
            u.username

         ORDER BY
            p.name,
            p.id'
    );

} else {

    $stmt = $mysqli->prepare(
        'SELECT
            p.id,
            p.name,
            p.owner_id,
            p.visibility,
            p.created_at,

            u.username AS owner_username,

            COUNT(DISTINCT t.id)
                AS tree_count,

            COUNT(DISTINCT pu_all.user_id)
                AS user_count,

            CASE
                WHEN p.owner_id = ?
                    THEN "owner"
                ELSE COALESCE(
                    pu.project_role,
                    "viewer"
                )
            END AS project_role

         FROM projects p

         LEFT JOIN users u
            ON u.id = p.owner_id

         LEFT JOIN trees t
            ON t.project_id = p.id

         LEFT JOIN project_users pu_all
            ON pu_all.project_id = p.id

         LEFT JOIN project_users pu
            ON pu.project_id = p.id
            AND pu.user_id = ?

         WHERE
            p.owner_id = ?
            OR pu.user_id = ?

         GROUP BY
            p.id,
            p.name,
            p.owner_id,
            p.visibility,
            p.created_at,
            u.username,
            pu.project_role

         ORDER BY
            p.name,
            p.id'
    );

    if ($stmt) {
        $stmt->bind_param(
            'iiii',
            $userId,
            $userId,
            $userId,
            $userId
        );
    }
}


$projects = [];

if ($stmt) {

    $stmt->execute();

    $result =
        $stmt->get_result();

    while (
        $project =
            $result->fetch_assoc()
    ) {
        $projects[] = $project;
    }

    $stmt->close();
}


$projectRoleLabels = [
    'owner'  => 'Vlastník',
    'admin'  => 'Administrátor',
    'editor' => 'Editor',
    'viewer' => 'Prohlížení'
];


require_once __DIR__
    . '/app/includes/header.php';

?>

<div class="admin-message admin-message--success">

    Přihlášen jako

    <strong>
        <?= htmlspecialchars(
            $username,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </strong>

</div>


<div class="admin-projects-toolbar">

    <div>
        <h2>
            Projekty
        </h2>

        <p>
            Projekty, ke kterým máte přístup.
        </p>
    </div>

    <?php if (
        $isSuperAdmin === 1
        || $role === 'admin'
    ): ?>

        <a
            href="/project_new.php"
            class="btn"
        >
            + Nový projekt
        </a>

    <?php endif; ?>

</div>


<?php if (!$projects): ?>

    <div class="admin-message">
        Nemáte přiřazen žádný projekt.
    </div>

<?php else: ?>

    <div class="project-list">

        <?php foreach ($projects as $project): ?>

            <?php

            $projectId =
                (int) $project['id'];

            $projectRole =
                (string) (
                    $project['project_role']
                    ?? 'viewer'
                );

            $projectRoleLabel =
                $projectRoleLabels[
                    $projectRole
                ]
                ?? $projectRole;

            $canManageProject =
                $isSuperAdmin === 1
                || in_array(
                    $projectRole,
                    [
                        'owner',
                        'admin'
                    ],
                    true
                );

            ?>

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

                        <div class="project-card__role">
                            <?= htmlspecialchars(
                                $projectRoleLabel,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

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
                        <?= $project['visibility'] === 'public'
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
                        <span>stromů</span>
                    </div>

                    <div class="project-stat">
                        <strong>
                            <?= (int) $project['user_count'] ?>
                        </strong>
                        <span>uživatelů</span>
                    </div>

                </div>


                <dl class="project-card__details">

                    <div>
                        <dt>Vlastník</dt>
                        <dd>
                            <?= htmlspecialchars(
                                $project['owner_username']
                                ?: 'Neuveden',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Vytvořen</dt>
                        <dd>
                            <?= htmlspecialchars(
                                (string) $project['created_at'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                </dl>


                <div class="project-card__actions">

                    <form
                        action="/switch_project.php"
                        method="post"
                    >
                        <input
                            type="hidden"
                            name="project_id"
                            value="<?= $projectId ?>"
                        >

                        <button
                            type="submit"
                            class="btn"
                        >
                            Otevřít
                        </button>
                    </form>


                    <?php if ($canManageProject): ?>

                        <a
                            href="/project_edit.php?id=<?= $projectId ?>"
                            class="btn btn-secondary"
                        >
                            Upravit
                        </a>

                        <a
                            href="/project_users.php?id=<?= $projectId ?>"
                            class="btn btn-secondary"
                        >
                            Uživatelé
                        </a>

                    <?php endif; ?>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<div class="admin-actions">

    <a
        href="/dashboard.php"
        class="btn btn-secondary"
    >
        Zpět na dashboard
    </a>

</div>

<?php

require_once __DIR__
    . '/app/includes/footer.php';

?>