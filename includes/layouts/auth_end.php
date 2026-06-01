<?php
/**
 * Auth page layout — bottom section (card close + scripts).
 *
 * Optional variables:
 *   ?array   $toast        Flash toast ['message', 'type']
 *   string[] $pageScripts  Extra <script> src paths
 */
?>
    </div><!-- /auth card -->

    <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-3"></div>

    <script src="../../assets/js/toast.js"></script>
    <?php foreach ($pageScripts ?? [] as $src): ?>
        <script src="<?= htmlspecialchars($src) ?>"></script>
    <?php endforeach; ?>
    <?php if ($toast ?? null): ?>
        <script>
            if (window.showToast) {
                showToast(
                    <?= json_encode($toast['message']) ?>,
                    <?= json_encode($toast['type']) ?>
                );
            }
        </script>
    <?php endif; ?>

</body>
</html>
