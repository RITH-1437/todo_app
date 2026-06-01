<?php
/**
 * Main application layout — bottom section.
 * Closes the container opened by includes/layouts/app.php.
 *
 * Optional variables:
 *   ?array  $toast        Flash toast ['message', 'type']
 *   string[] $pageScripts  Extra <script> src tags to append
 */
?>
    </div><!-- /max-w-7xl container -->

    <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-3"></div>

    <?php if (($toast ?? null)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.showToast) {
                    window.showToast(
                        <?= json_encode($toast['message']) ?>,
                        <?= json_encode($toast['type']) ?>
                    );
                }
            });
        </script>
    <?php endif; ?>

    <script src="../assets/js/toast.js"></script>
    <script src="../assets/js/theme.js"></script>
    <?php foreach ($pageScripts ?? [] as $src): ?>
        <script src="<?= htmlspecialchars($src) ?>"></script>
    <?php endforeach; ?>

</body>
</html>
