<style>
.drag-and-drop {
    display: flex !important;
    width: 100% !important;
    margin: 10px 0 30px 0 !important;
    gap: 16px !important;
    align-items: stretch !important;
}
.drag-and-drop ul {
    list-style: none !important;
    margin: 0 !important;
    padding: 12px !important;
}
.drag-and-drop__container {
    position: relative !important;
    width: calc(50% - 24px) !important;
    flex: 1 !important;
    background-color: #fff !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 4px !important;
    display: flex !important;
    flex-direction: column !important;
    min-height: 420px !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
}
.drag-and-drop__divider {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 4px !important;
    font-size: 22px !important;
    align-self: center !important;
    color: #475467 !important;
    user-select: none !important;
}
.drag-and-drop__title {
    background-color: #fff !important;
    border-bottom: 1px solid #dee2e6 !important;
    padding: 12px 16px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    margin: 0 !important;
    height: 44px !important;
    display: flex !important;
    align-items: center !important;
    color: #344054 !important;
    letter-spacing: -0.01em !important;
}
.drag-and-drop__items {
    position: relative !important;
    flex-grow: 1 !important;
    min-height: 350px !important;
    padding: 12px !important;
    margin: 0 !important;
    list-style: none !important;
}
.drag-and-drop__item {
    display: flex !important;
    align-items: center !important;
    padding: 12px 16px !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 4px !important;
    line-height: 1.4 !important;
    cursor: grab !important;
    background-color: #fff !important;
    margin-bottom: 8px !important;
    font-size: 14px !important;
    color: #1d2939 !important;
    user-select: none !important;
    transition: background-color 0.15s ease, border-color 0.15s ease !important;
}
.drag-and-drop__item:hover {
    background-color: #e9ecef !important;
}
.drag-and-drop__item:active {
    cursor: grabbing !important;
    background-color: #e2e8f0 !important;
}
.drag-and-drop__item.sortable-ghost {
    opacity: 0.35 !important;
    background-color: #e9ecef !important;
    border-style: dashed !important;
}
.drag-and-drop__item .icon-arrows-alt {
    margin-right: 12px !important;
    color: #667085 !important;
    font-size: 14px !important;
    display: inline-block !important;
}
</style>

<?php if ($this->previewMode): ?>
    <div class="form-control">
        <?= e(implode(', ', $displayFieldNames)) ?>
    </div>
<?php else: ?>
    <div id="widget_<?= $widget_id ?>" class="drag-and-drop" data-input-name="<?= $name ?>[]">
        <input type="hidden" name="<?= $name ?>" id="input_<?= $widget_id ?>" value="<?= e(json_encode($displayFieldNames)) ?>" />
        <div class="metafields-inputs">
            <?php foreach ($visibleFields as $item): ?>
                <input type="hidden" name="<?= $name ?>[]" value="<?= e($item->name) ?>" />
            <?php endforeach ?>
        </div>

        <!-- Левая колонка: from_label -->
        <div class="drag-and-drop__container drag-and-drop__container--from">
            <h4 class="drag-and-drop__title"><?= e($from_label) ?></h4>
            <ul id="from_<?= $widget_id ?>" class="drag-and-drop__items drag-and-drop__items--from">
                <?php foreach ($availableFields as $item): ?>
                    <li class="drag-and-drop__item" data-field-name="<?= e($item->name) ?>" data-id="<?= $item->id ?? '' ?>">
                        <i class="icon-arrows-alt"></i>
                        <span><?= e($item->label) ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>

        <!-- Разделитель -->
        <div class="drag-and-drop__divider">⇄</div>

        <!-- Правая колонка: to_label -->
        <div class="drag-and-drop__container drag-and-drop__container--to">
            <h4 class="drag-and-drop__title"><?= e($to_label) ?></h4>
            <ul id="to_<?= $widget_id ?>" class="drag-and-drop__items drag-and-drop__items--to">
                <?php foreach ($visibleFields as $item): ?>
                    <li class="drag-and-drop__item" data-field-name="<?= e($item->name) ?>" data-id="<?= $item->id ?? '' ?>">
                        <i class="icon-arrows-alt"></i>
                        <span><?= e($item->label) ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>

    <script>
        (function() {
            var attempts = 0;
            function start() {
                attempts++;
                if (window.initWikixMetaFieldsWidget && window.Sortable) {
                    var ok = window.initWikixMetaFieldsWidget('<?= $widget_id ?>', '<?= $handlerUrl ?>');
                    if (!ok && attempts < 20) {
                        setTimeout(start, 50);
                    }
                } else if (attempts < 20) {
                    setTimeout(start, 50);
                }
            }
            start();
        })();
    </script>
<?php endif ?>
