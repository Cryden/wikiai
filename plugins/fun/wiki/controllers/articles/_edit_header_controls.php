<div class="d-flex align-items-center gap-2">
    <?php if ($template = ($model?->template ?? ($model?->template_id ? \Fun\Wiki\Models\Template::find($model->template_id) : null))): ?>
        <span class="badge bg-secondary text-white px-2 py-1 fs-6">
            <i class="icon-file-text"></i> <?= e($template->name) ?>
        </span>
    <?php endif ?>
    <span class="badge <?= ($model?->status ?? 'draft') === 'published' ? 'bg-success' : (($model?->status ?? '') === 'skeleton' ? 'bg-info' : 'bg-warning') ?> text-white px-2 py-1 fs-6">
        <?= e(($model?->status ?? 'draft') === 'published' ? 'Опубликовано' : (($model?->status ?? '') === 'skeleton' ? 'Заготовка' : 'Черновик')) ?>
    </span>
</div>

