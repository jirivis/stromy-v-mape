<?php

/*
---------------------------------------------------
 Stromy v mapě
 Společná patička administrace
---------------------------------------------------
*/

$showCopyright = $showCopyright ?? true;
?>

        <?php if (!empty($dashboardNavigation)): ?>
        <nav class="admin-actions dashboard-navigation" aria-label="Návrat do přehledu na konci stránky"><a class="btn btn-secondary" href="/dashboard.php">← Dashboard</a></nav>
        <?php endif; ?>
        <?php if ($showCopyright): ?>
            <footer class="admin-footer">
                <?= htmlspecialchars(APP_COPYRIGHT) ?>
            </footer>
        <?php endif; ?>

    </main>
</div>

</body>
</html>