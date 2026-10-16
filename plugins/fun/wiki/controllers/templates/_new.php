<?php if (!$this->fatalError): ?>
    <?= Form::open(['id' => 'tailor-form', 'class' => 'position-relative h-100', 'data-change-monitor' => true]) ?>
    <div class="position-relative h-100" data-control="vue-app">
        <input type="hidden" name="Template[type]" id="template-type-input" value="<?= e($formModel->type ?? ($initialState['contentGroup'] ?? 'article')) ?>" />
        <div class="padded-container d-flex flex-column h-100 form-document-layout">

            <div class="document-header">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <?= $this->formRender([
                            'section' => 'outside'
                        ]) ?>
                    </div>
                    <div>
                        <?= $this->makePartial('edit_header_controls', ['model' => $formModel ?? $this->formGetModel()]) ?>
                    </div>
                </div>
            </div>

            <div data-control="vue-template-document">
                <div class="padded-container-inset" data-vue-template>
                    <template>
                        <backend-document
                            :processing="state.processing"
                            :toolbar-command-event-bus="state.eventBus">
                            <template v-slot:toolbar>
                                <backend-document-toolbar
                                    :elements="state.toolbarElements"
                                    @command="onCommand"
                                    :disabled="state.toolbarDisabled">
                                </backend-document-toolbar>
                            </template>
                        </backend-document>
                    </template>
                </div>
            </div>

            <div class="flex-grow-1 d-flex flex-column form-document-primary" id="entryPrimaryTabs">
                <?= $this->makePartial('primary_tabs') ?>
            </div>
        </div>

        <script type="text/template" data-vue-state="initial"><?= json_encode($initialState) ?></script>
        <script type="text/template" data-vue-lang><?= json_encode($langState) ?></script>
    </div>
    <?= Form::close() ?>
<?php else: ?>
    <div class="padded-container">
        <p class="flash-message static error"><?= e(__($this->fatalError)) ?></p>

        <p><?= Ui::button()->label('Вернуться к списку')->linkTo('fun/wiki/templates') ?></p>
    </div>
<?php endif ?>
