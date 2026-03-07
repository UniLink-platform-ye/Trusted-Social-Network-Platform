<div class="empty-state">
    <div class="empty-icon"><i class="fa-regular fa-folder-open"></i></div>
    <h3><?= e($title ?? 'No data found'); ?></h3>
    <p><?= e($description ?? 'Try adjusting filters or adding new records.'); ?></p>
    <?php if (!empty($actionText) && !empty($actionLink)): ?>
        <a href="<?= e($actionLink); ?>" class="btn btn-primary"><?= e($actionText); ?></a>
    <?php endif; ?>
</div>
