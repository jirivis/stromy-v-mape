<?php

/*
---------------------------------------------------
 Stromy v mapě
 Společná patička administrace
---------------------------------------------------
*/

$showCopyright = $showCopyright ?? true;
?>

        <?php if ($showCopyright): ?>
            <footer class="admin-footer">
                <?= htmlspecialchars(APP_COPYRIGHT) ?>
            </footer>
        <?php endif; ?>

    </main>
</div>

</body>
</html>