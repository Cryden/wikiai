<?php declare(strict_types=1);

namespace Fun\Wiki\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\Behaviors\RelationController;
use Backend\VueComponents\Document;
use Backend\VueComponents\Popover;
use Backend\VueComponents\DropdownMenuButton;
use Fun\Wiki\Models\Template;
use Lang;

class Templates extends Controller
{
    public $implement = [
        FormController::class,
        ListController::class,
        RelationController::class,
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';

    public $requiredPermissions = ['fun.wiki.manage_templates'];

    public $viewWidget;

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Fun.Wiki', 'wiki', 'templates');
    }

    public function beforeDisplay()
    {
        $this->addViewFieldsWidget();
    }

    protected function addViewFieldsWidget(): void
    {
        $model = null;
        try {
            $model = $this->formGetModel();
        } catch (\Throwable $e) {
        }

        if (!$model) {
            $params = $this->getParams();
            if (!empty($params[0])) {
                $model = Template::find($params[0]);
            }
        }

        if (!$model) {
            $id = post('manage_id') ?? post('id') ?? request()->route('record_id');
            if ($id) {
                $model = Template::find($id);
            }
        }

        if (!$model) {
            return;
        }

        $config = $this->makeConfig([
            'fields' => [
                'display_fields' => [
                    'type'       => 'wikix_meta_fields',
                    'from_label' => 'Data fields',
                    'to_label'   => 'MetaData',
                ]
            ]
        ]);

        $config->alias = 'metaFieldsView';
        $config->model = $model;

        $this->viewWidget = $this->makeWidget(\Backend\Widgets\Form::class, $config);
        $this->viewWidget->bindToController();
    }

    public function onUpdateViewFields()
    {
        if (!$this->viewWidget) {
            $this->addViewFieldsWidget();
        }

        return $this->makePartial('view_fields_form', ['widget' => $this->viewWidget]);
    }

    public function onSetVisible()
    {
        return $this->onSetDisplayFields();
    }

    public function onSetDisplayFields()
    {
        $model = null;
        try {
            $model = $this->formGetModel();
        } catch (\Throwable $e) {
        }

        if (!$model) {
            $params = $this->getParams();
            if (!empty($params[0])) {
                $model = Template::find($params[0]);
            }
        }

        $data = post('value') ?? post('display_fields') ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }

        $cleaned = is_array($data) ? array_values(array_filter(array_map('trim', $data))) : [];

        if ($model && $model->exists) {
            $model->display_fields = $cleaned;
            $model->save();
        }

        return ['status' => 'success', 'value' => $cleaned];
    }

    public function create()
    {
        $this->bodyClass = 'compact-container';
        $this->loadDocumentAssets();

        $response = $this->asExtension('FormController')->create();
        if ($response) {
            return $response;
        }

        $this->prepareVars();
    }

    public function update($recordId = null)
    {
        $this->bodyClass = 'compact-container';
        $this->loadDocumentAssets();

        $response = $this->asExtension('FormController')->update($recordId);
        if ($response) {
            return $response;
        }

        $this->prepareVars($this->formGetModel());
    }

    protected function loadDocumentAssets(): void
    {
        $this->addJs('/plugins/fun/wiki/controllers/templates/assets/js/vue-template-document.js');
        $this->addJs('/plugins/fun/wiki/assets/js/vue-template-header-controls.js');
        $this->addCss('/plugins/fun/wiki/assets/css/publishbutton.css');
        $this->addCss('/plugins/fun/wiki/formwidgets/metafields/assets/css/metafields.css');
        $this->addJs('/plugins/fun/wiki/formwidgets/metafields/assets/js/sortable.min.js');
        $this->addJs('/plugins/fun/wiki/formwidgets/metafields/assets/js/metafields.js');

        $this->registerVueComponent(Document::class);
        $this->registerVueComponent(Popover::class);
        $this->registerVueComponent(DropdownMenuButton::class);
    }

    protected function prepareVars($model = null): void
    {
        $this->vars['initialState'] = $this->makeInitialState($model);
        $this->vars['langState'] = $this->makeLangState();
    }

    public function makeInitialState($model = null): array
    {
        $type = $model?->type ?? 'article';

        return [
            'showEntryTypeSelector' => true,
            'entryTypeOptions' => [
                'article' => 'Статья',
                'node'    => 'Узел',
                'variant' => 'Вариант',
            ],
            'contentGroup' => $type,
        ];
    }

    public function makeLangState(): array
    {
        return [
            'form_save'            => 'Сохранить',
            'form_save_close'      => 'Сохранить и закрыть',
            'form_delete'          => 'Удалить',
            'form_confirm_delete'  => 'Вы действительно хотите удалить этот шаблон?',
            'form_error'           => e(Lang::get('backend::lang.form.error')),
        ];
    }

    /**
     * AJAX обработчик переключения типа сущности (Статья / Узел / Вариант)
     */
    public function onChangeEntryType()
    {
        return $this->onSetEntryType();
    }

    /**
     * Переключение типа сущности (Статья / Узел / Вариант) через выпадающее меню
     */
    public function onSetEntryType()
    {
        $type = post('entry_type')
            ?? post('Template.type')
            ?? post('EntryRecord.content_group')
            ?? post('command');

        $model = $this->formGetModel();

        if ($model && in_array($type, ['article', 'node', 'variant'], true)) {
            $model->type = $type;
            if ($model->exists) {
                $model->save();
            }
        }

        $widget = $this->formGetWidget();
        if ($widget) {
            $widget->setFormValues();
        }

        $this->prepareVars($model);

        return [
            '#entryPrimaryTabs' => $this->makePartial('primary_tabs')
        ];
    }

    /**
     * Перестроение дерева шаблонов и синхронизация связей (аналог Tailor onResetStructure)
     */
    public function onResetStructure()
    {
        $model = new Template;
        $model->resetTreeNesting();
        $model->resetTreeOrphans();

        foreach (Template::cursor() as $template) {
            $template->syncAncestryFields();
        }

        \Flash::success('Структура дерева шаблонов успешно перестроена');

        return $this->listRefresh();
    }

    /**
     * Открытие модального окна со списком доступных типов полей
     */
    public function onRelationButtonAddField()
    {
        $this->vars['fieldTypes'] = \Fun\Wiki\Classes\FieldTypeRegistry::instance()->getAll();

        return $this->makePartial('manage_fields');
    }

    /**
     * Расширение формы управления связанной записью поля
     */
    public function relationExtendManageFormWidget($form, $field, $model)
    {
        if (!$form->model instanceof \Fun\Wiki\Models\Field) {
            return;
        }

        $type = post('Field.type') ?? input('Field.type') ?? $form->model->type;
        if ($type) {
            $form->model->type = $type;
        }
    }

    /**
     * Интерактивное обновление родительских полей при изменении настроек шаблона
     */
    public function onUpdateFields()
    {
        $model = $this->formGetModel();
        if ($model) {
            $templateData = post('Template') ?? [];
            if (array_key_exists('parent', $templateData)) {
                $model->parent_id = !empty($templateData['parent']) ? (int) $templateData['parent'] : null;
            }
            if (array_key_exists('is_nested', $templateData)) {
                $model->is_nested = (bool) $templateData['is_nested'];
            }
            if (array_key_exists('is_auto_create', $templateData)) {
                $model->is_auto_create = (bool) $templateData['is_auto_create'];
            }
            if (array_key_exists('is_mandatory', $templateData)) {
                $model->is_mandatory = (bool) $templateData['is_mandatory'];
            }

            $model->save();
            $model->syncAncestryFields();
        }

        return [
            '#entryPrimaryTabs' => $this->makePartial('primary_tabs')
        ];
    }

    /**
     * Скачивание экспорта всех шаблонов и полей в формате JSON
     */
    public function export()
    {
        $service = new \Fun\Wiki\Services\TemplateTransferService();
        $data = $service->export();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'wiki_templates_' . date('Y-m-d_His') . '.json';

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    /**
     * Открытие модального окна для импорта шаблонов
     */
    public function onLoadImportPopup()
    {
        return $this->makePartial('import_modal');
    }

    /**
     * Обработка импорта шаблонов из загруженного файла или вставленного JSON
     */
    public function onImportTemplates()
    {
        try {
            $service = new \Fun\Wiki\Services\TemplateTransferService();

            $jsonContent = null;
            if (request()->hasFile('import_file') && ($file = request()->file('import_file')) && $file->isValid()) {
                $jsonContent = $file->getContent();
            } else {
                $jsonContent = trim((string) post('import_json'));
            }

            if (empty($jsonContent)) {
                throw new \October\Rain\Exception\ValidationException([
                    'import_json' => 'Загрузите JSON-файл или вставьте содержимое JSON в текстовое поле.'
                ]);
            }

            $truncate = (bool) post('truncate_existing', false);
            $result = $service->import($jsonContent, $truncate);

            \Flash::success("Импорт завершен! Импортировано шаблонов: {$result['templates']}, полей: {$result['fields']}.");

            return \Redirect::refresh();
        } catch (\October\Rain\Exception\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Template import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            \Flash::error('Ошибка импорта: ' . $e->getMessage());
            return \Redirect::refresh();
        }
    }
}
