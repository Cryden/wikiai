<div class="modal-header">
    <h4 class="modal-title"><?= __("Выберите тип поля") ?></h4>
    <button type="button" class="btn-close" data-dismiss="popup"></button>
</div>
<div class="modal-body">
    <div class="control-filelist filelist-hero is-selectable is-scrollable size-large border mb-4" data-control="filelist">
        <ul>
            <?php foreach ($fieldTypes as $key => $fieldType): ?>
                <?php if ($key === 'parent') continue; ?>
                <li>
                    <a
                        data-control="popup"
                        data-handler="onRelationButtonCreate"
                        data-disposable
                        data-request-data="Field[type]: '<?= e($fieldType->getCode()) ?>', _relation_field: 'fields'"
                        href="javascript:;">
                        <i class="list-icon <?= e($fieldType->getIcon()) ?>"></i>
                        <span class="title"><?= e(__($fieldType->getLabel())) ?></span>
                        <span class="description"><?= e(__($fieldType->getDescription())) ?></span>
                        <span class="borders"></span>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
</div>
<div class="modal-footer">
    <?= Ui::button(__("Отмена"))->dismissPopup() ?>
</div>

