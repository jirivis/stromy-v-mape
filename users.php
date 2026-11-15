<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

$role =
    $_SESSION['role']
    ?? 'viewer';

$isSuperAdmin =
    (int) (
        $_SESSION['is_super_admin']
        ?? 0
    );

/*
 * Správa uživatelů je globální administrace.
 */
if ($isSuperAdmin !== 1) {
    http_response_code(403);

    die(
        'Správu uživatelských účtů může provádět '
        . 'pouze superadministrátor.'
    );
}


/*
---------------------------------------------------
 Načtení uživatelů
---------------------------------------------------
*/

$users = [];

$stmt =
    $mysqli->prepare(
        'SELECT
            u.id,
            u.username,
            u.email,
            u.role,
            u.is_super_admin,
            u.active,
            u.created_at,
            u.last_login,

            COUNT(
                DISTINCT pu.project_id
            ) AS project_count

         FROM users u

         LEFT JOIN project_users pu
            ON pu.user_id = u.id

         GROUP BY
            u.id,
            u.username,
            u.email,
            u.role,
            u.is_super_admin,
            u.active,
            u.created_at,
            u.last_login

         ORDER BY
            u.username,
            u.id'
    );

if ($stmt) {

    $stmt->execute();

    $result =
        $stmt->get_result();

    while (
        $user =
            $result->fetch_assoc()
    ) {
        $users[] = $user;
    }

    $stmt->close();
}


$roleLabels = [
    'admin' =>
        'Administrátor',

    'editor' =>
        'Editor',

    'client' =>
        'Zákazník',

    'viewer' =>
        'Prohlížení'
];


$pageTitle =
    'Uživatelé';

$pageSubtitle =
    'Správa uživatelských účtů';

$cardClass =
    'admin-card admin-card--wide';


require_once __DIR__
    . '/app/includes/header.php';

?>


<div class="admin-projects-toolbar">

    <div>

        <h2>
            Uživatelé
        </h2>

        <p>
            Správa účtů,
            globálních rolí a přístupů.
        </p>

    </div>
<div class="admin-toolbar-actions"><a class="btn btn-secondary" href="/dashboard.php">← Dashboard</a>

    <a
        href="/user_new.php"
        class="btn"
    >
        + Nový uživatel
    </a>

</div>
</div>


<?php if (!$users): ?>

    <div class="admin-message">
        Nejsou vytvořeni žádní uživatelé.
    </div>

<?php else: ?>

    <div class="user-list">

        <?php foreach (
            $users
            as $user
        ): ?>

            <?php

            $userRole =
                (string) (
                    $user['role']
                    ?? 'viewer'
                );

            $userRoleLabel =
                $roleLabels[
                    $userRole
                ]
                ?? $userRole;

            $isUserSuperAdmin =
                (int) (
                    $user['is_super_admin']
                    ?? 0
                ) === 1;

            $isActive =
                (int) (
                    $user['active']
                    ?? 0
                ) === 1;

            ?>

            <article class="user-card">

                <div class="user-card__header">

                    <div>

                        <h3>
                            <?= htmlspecialchars(
                                $user['username'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>

                        <?php if (
                            !empty(
                                $user['email']
                            )
                        ): ?>

                            <div class="user-card__email">

                                <?= htmlspecialchars(
                                    $user['email'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <span
                        class="
                            user-status
                            <?= $isActive
                                ? 'user-status--active'
                                : 'user-status--inactive'
                            ?>
                        "
                    >
                        <?= $isActive
                            ? 'Aktivní'
                            : 'Neaktivní'
                        ?>
                    </span>

                </div>


                <div class="user-card__badges">

                    <span class="user-badge">
                        <?= htmlspecialchars(
                            $userRoleLabel,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <?php if ($isUserSuperAdmin): ?>

                        <span
                            class="
                                user-badge
                                user-badge--super
                            "
                        >
                            Superadmin
                        </span>

                    <?php endif; ?>

                </div>


                <div class="project-card__stats">

                    <div class="project-stat">

                        <strong>
                            <?= (int) $user['project_count'] ?>
                        </strong>

                        <span>
                            projektů
                        </span>

                    </div>

                </div>


                <dl class="project-card__details">

                    <div>

                        <dt>
                            Vytvořen
                        </dt>

                        <dd>
                            <?= htmlspecialchars(
                                (string) $user['created_at'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>

                    </div>


                    <div>

                        <dt>
                            Poslední přihlášení
                        </dt>

                        <dd>
                            <?= !empty($user['last_login'])
                                ? htmlspecialchars(
                                    (string) $user['last_login'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                                : 'Dosud nepřihlášen'
                            ?>
                        </dd>

                    </div>

                </dl>


                <div class="project-card__actions">

                    <a
                        href="/user_edit.php?id=<?= (int) $user['id'] ?>"
                        class="btn btn-secondary"
                    >
                        Upravit
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