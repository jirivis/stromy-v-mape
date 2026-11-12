<?php

/*
---------------------------------------------------
 Stromy v mapě
 Společná horní lišta
---------------------------------------------------
*/

$topbarTitle = $topbarTitle ?? APP_NAME;
$topbarProject = $topbarProject ?? '';
$topbarUsername = $topbarUsername ?? ($_SESSION['username'] ?? 'Uživatel');
$showDashboardLink = $showDashboardLink ?? true;
?>

<header class="app-topbar">

    <div class="app-topbar__left">

		<a
			href="/dashboard.php"
			class="app-topbar__brand"
			target="_blank"
			rel="noopener noreferrer"
		>
			<?= htmlspecialchars($topbarTitle) ?>
		</a>

    </div>
	
	<div class="app-topbar__center">

    <?php if ($topbarProject !== ''): ?>

        <span class="app-topbar__project">
            <?= htmlspecialchars(
                $topbarProject,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </span>

    <?php endif; ?>

</div>

    <div class="app-topbar__right">
        <?php if(!empty($showProjectTableLink)): ?><a class="app-topbar__button" href="/map/public/project_tools/table.php">Tabulka projektu</a><?php endif; ?>

        <span class="app-topbar__user">
            <?= htmlspecialchars($topbarUsername) ?>
        </span>

        <?php if ($showDashboardLink): ?>
            <a href="/dashboard.php" class="app-topbar__button">
                Administrace
            </a>
        <?php endif; ?>

        <a href="/logout.php" class="app-topbar__button app-topbar__button--logout">
            Odhlásit
        </a>

    </div>

</header>