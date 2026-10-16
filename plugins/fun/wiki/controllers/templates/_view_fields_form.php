<div id="<?= $this->getId('popup') ?>" class="meta_fields-popup">
    <?= Form::open([
        'data-request-parent-form' => "#{$this->getId()}"
    ]) ?>

    <div class="modal-header">
        <h4 class="modal-title"><?= e('Настройка отображения полей (Инфобокс)') ?></h4>
        <button type="button" class="btn-close" data-dismiss="popup"></button>
    </div>

    <div class="meta_fields-list list-flush p-3">
        <?= $widget ? $widget->render() : '' ?>
    </div>

    <div class="modal-footer">
        <button
            type="button"
            class="btn btn-primary"
            data-dismiss="popup">
            <?= e('Готово') ?>
        </button>
    </div>
    <?= Form::close() ?>
</div>

