<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? 'viewer';
$isSuperAdmin = (int) ($_SESSION['is_super_admin'] ?? 0);

if (
    $isSuperAdmin !== 1
    && $role !== 'admin'
) {
    http_response_code(403);
    die('Nemáte oprávnění spravovat zákazníky.');
}

$pageTitle = 'Zákazníci';
$pageSubtitle = 'Správa zákazníků';
$cardClass = 'admin-card admin-card--wide';

$stmt = $mysqli->prepare(
    'SELECT
        c.id,
        c.name,
        c.ico,
        c.address,
        c.email,
        c.phone,
        c.note,
        c.created_at,
        COUNT(DISTINCT p.id) AS project_count
     FROM clients c
     LEFT JOIN projects p
        ON p.client_id = c.id
     GROUP BY
        c.id,
        c.name,
        c.ico,
        c.address,
        c.email,
        c.phone,
        c.note,
        c.created_at
     ORDER BY c.name, c.id'
);

$clients = [];

if ($stmt) {
    $stmt->execute();

    $result = $stmt->get_result();

    while ($client = $result->fetch_assoc()) {
        $clients[] = $client;
    }

    $stmt->close();
}

require_once __DIR__ . '/app/includes/header.php';
?>

<div class="admin-projects-toolbar">

    <div>
        <h2>Zákazníci</h2>
        <p>Subjekty, pro které jsou vedeny projekty.</p>
    </div>

    <a
        href="/client_new.php"
        class="btn"
    >
        + Nový zákazník
    </a>

</div>

<?php if (!$clients): ?>

    <div class="admin-message">
        Zatím není založen žádný zákazník.
    </div>

<?php else: ?>

    <div class="project-list">

        <?php foreach ($clients as $client): ?>

            <article class="project-card">

                <div class="project-card__header">

                    <div>
                        <h3>
                            <?= htmlspecialchars(
                                $client['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>
                    </div>

                </div>

                <div class="project-card__stats">

                    <div class="project-stat">
                        <strong>
                            <?= (int) $client['project_count'] ?>
                        </strong>
                        <span>projektů</span>
                    </div>

                </div>

                <dl class="project-card__details">

                    <?php if (!empty($client['ico'])): ?>
                        <div>
                            <dt>IČO</dt>
                            <dd>
                                <?= htmlspecialchars(
                                    $client['ico'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($client['address'])): ?>
                        <div>
                            <dt>Adresa</dt>
                            <dd>
                                <?= htmlspecialchars(
                                    $client['address'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($client['email'])): ?>
                        <div>
                            <dt>E-mail</dt>
                            <dd>
                                <a href="mailto:<?= htmlspecialchars(
                                    $client['email'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                                    <?= htmlspecialchars(
                                        $client['email'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </a>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($client['phone'])): ?>
                        <div>
                            <dt>Telefon</dt>
                            <dd>
                                <?= htmlspecialchars(
                                    $client['phone'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                </dl>

                <div class="project-card__actions">

                    <a
                        href="/client_edit.php?id=<?= (int) $client['id'] ?>"
                        class="btn btn-secondary"
                    >
                        Upravit
                    </a>

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
require_once __DIR__ . '/app/includes/footer.php';
?>