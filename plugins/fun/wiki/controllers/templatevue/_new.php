<?php if (!$this->fatalError): ?>
    <?= Form::open(['id' => 'tailor-form', 'class' => 'position-relative h-100', 'data-change-monitor' => true]) ?>
    <div class="position-relative h-100" data-control="vue-app">
        <input type="hidden" name="Template[type]" id="template-type-input" value="<?= e($formModel->type ?? ($initialState['contentGroup'] ?? 'article')) ?>" />
        <div class="padded-container d-flex flex-column h-100 form-document-layout">

            <div class="document-header">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <div class="form-tabless-fields">
                            <div class="form-group primary-title-field">
                                <label class="form-label">Имя шаблона</label>
                                <input
                                    type="text"
                                    name="Template[name]"
                                    id="Form-field-Template-name"
                                    class="form-control"
                                    placeholder="Имя шаблона"
                                    value="<?= e($formModel->name ?? '') ?>"
                                    autocomplete="off" />
                            </div>
                        </div>
                    </div>
                    <div>
                        <?= $this->makePartial('$/fun/wiki/controllers/templates/_edit_header_controls.php', ['model' => $formModel]) ?>
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
                <?= $this->makePartial('primary_tabs', [
                    'template'     => $formModel,
                    'formModel'    => $formModel,
                    'templateData' => $templateData,
                ]) ?>
            </div>
        </div>

        <script type="text/template" data-vue-state="initial"><?= json_encode($initialState) ?></script>
        <script type="text/template" data-vue-lang><?= json_encode($langState) ?></script>
    </div>
    <?= Form::close() ?>
<?php else: ?>
    <div class="padded-container">
        <p class="flash-message static error"><?= e(__($this->fatalError)) ?></p>
        <p><?= Ui::button()->label('Вернуться к списку')->linkTo('fun/wiki/templatevue') ?></p>
    </div>
<?php endif ?>

