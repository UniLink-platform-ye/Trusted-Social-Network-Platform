    <footer class="app-footer">
        <p>&copy; <?= date('Y'); ?> Trusted Social Network Platform. Admin modules for graduation project.</p>
    </footer>
</div>
</div>

<script>
(function () {
    let hidden = false;

    function hideLoader() {
        if (hidden) {
            return;
        }

        const loader = document.getElementById('appLoader');
        if (!loader) {
            return;
        }

        hidden = true;
        loader.classList.add('hidden');
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(hideLoader, 80);
    }

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(hideLoader, 80);
    }, { once: true });

    window.addEventListener('load', function () {
        setTimeout(hideLoader, 120);
    }, { once: true });

    // Fallback if external scripts are slow/not loaded.
    setTimeout(hideLoader, 3000);
})();
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset_url('js/app.js')); ?>?v=<?= time() ?>"></script>
<?php foreach ($pageScripts as $script): ?>
    <script src="<?= e(asset_url('js/' . $script)); ?>?v=<?= time() ?>"></script>
<?php endforeach; ?>
<?php if ($success = flash('success')): ?>
<script>
    showToast('success', <?= json_encode($success, JSON_UNESCAPED_UNICODE); ?>);
</script>
<?php endif; ?>
<?php if ($error = flash('error')): ?>
<script>
    showToast('error', <?= json_encode($error, JSON_UNESCAPED_UNICODE); ?>);
</script>
<?php endif; ?>
</body>
</html>
