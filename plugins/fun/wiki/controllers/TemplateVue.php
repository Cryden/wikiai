<?php declare(strict_types=1);

namespace Fun\Wiki\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Backend\Behaviors\ListController;
use Backend\VueComponents\Document;
use Backend\VueComponents\Popover;
use Backend\VueComponents\DropdownMenuButton;
use Fun\Wiki\Models\Template;
use Fun\Wiki\Models\Field;
use Fun\Wiki\Classes\FieldTypeRegistry;
use Fun\Wiki\VueComponents\TemplateEditor;
use Illuminate\Support\Facades\DB;
use October\Rain\Exception\ValidationException;
use Flash;
use Redirect;
use Validator;
use Lang;

class TemplateVue extends Controller
{
    public $implement = [
        ListController::class,
    ];

    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['fun.wiki.manage_templates'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Fun.Wiki', 'wiki', 'templatevue');
    }

    public function create()
    {
        $this->pageTitle = 'Новый шаблон';
        $this->bodyClass = 'compact-container';
        $this->loadDocumentAssets();

        $template = new Template();
        $template->type = 'article';
        $template->is_mandatory = false;
        $template->is_auto_create = false;
        $template->is_nested = false;

        $this->vars['formModel'] = $template;
        $this->vars['template'] = $template;
        $this->vars['templateData'] = $this->prepareTemplatePayload($template);
        $this->prepareVars($template);
    }

    public function update($recordId = null)
    {
        $this->pageTitle = 'Редактирование шаблона';
        $this->bodyClass = 'compact-container';
        $this->loadDocumentAssets();

        $template = Template::with(['fields' => fn($q) => $q->orderBy('sort_order', 'asc')])->findOrFail($recordId);

        $this->vars['formModel'] = $template;
        $this->vars['template'] = $template;
        $this->vars['templateData'] = $this->prepareTemplatePayload($template);
        $this->prepareVars($template);
    }

    protected function loadDocumentAssets(): void
    {
        $this->addJs('/plugins/fun/wiki/controllers/templates/assets/js/vue-template-document.js');
        $this->addJs('/plugins/fun/wiki/assets/js/vue-template-header-controls.js');
        $this->addCss('/plugins/fun/wiki/assets/css/publishbutton.css');

        $this->registerVueComponent(Document::class);
        $this->registerVueComponent(Popover::class);
        $this->registerVueComponent(DropdownMenuButton::class);
        $this->registerVueComponent(TemplateEditor::class);
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
            'isCreateAction'        => !$model?->exists,
            'showEntryTypeSelector' => true,
            'entryTypeOptions'      => [
                'article' => 'Статья',
                'node'    => 'Узел',
                'variant' => 'Вариант',
            ],
            'contentGroup'          => $type,
        ];
    }

    public function makeLangState(): array
    {
        return [
            'form_save'            => 'Сохранить',
            'form_save_close'      => 'Сохранить и закрыть',
            'form_delete'          => 'Удалить',
            'form_confirm_delete'  => 'Вы действительно хотите удалить этот шаблон?',
            'delete_entry_confirm' => 'Вы действительно хотите удалить этот шаблон?',
            'form_error'           => e(Lang::get('backend::lang.form.error')),
        ];
    }

    protected function prepareTemplatePayload(Template $template): array
    {
        $fields = $template->exists
            ? $template->fields->map(fn($f) => [
                'id'               => (int) $f->id,
                'template_id'      => (int) $f->template_id,
                'label'            => (string) $f->label,
                'name'             => (string) $f->name,
                'type'             => (string) $f->type,
                'type_name'        => (string) $f->type_name,
                'is_mandatory'     => (bool) $f->is_mandatory,
                'is_global'        => (bool) $f->is_global,
                'is_parent_field'  => (bool) $f->is_parent_field,
                'is_inherited'     => (bool) $f->is_inherited,
                'sort_order'       => (int) $f->sort_order,
                'depends_on_field' => $f->depends_on_field,
                'depends_on_value' => $f->depends_on_value,
                'config'           => is_array($f->config) ? $f->config : [],
            ])->values()->all()
            : [];

        // Parent templates (exclude self and children to avoid circular nesting)
        $parentQuery = Template::query();
        if ($template->exists) {
            $excludeIds = $template->getAllChildrenAndSelf()->pluck('id')->all();
            $parentQuery->whereNotIn('id', $excludeIds);
        }
        $parentTemplates = $parentQuery->orderBy('name')->get(['id', 'name', 'code', 'type'])->toArray();

        // Field types from registry
        $fieldTypes = [];
        foreach (FieldTypeRegistry::instance()->getAll() as $code => $typeObj) {
            $fieldTypes[] = [
                'code'        => $code,
                'label'       => $typeObj->getLabel(),
                'description' => method_exists($typeObj, 'getDescription') ? $typeObj->getDescription() : '',
            ];
        }

        return [
            'template' => [
                'id'             => $template->id,
                'name'           => (string) ($template->name ?? ''),
                'code'           => (string) ($template->code ?? ''),
                'type'           => (string) ($template->type ?: 'article'),
                'parent_id'      => $template->parent_id,
                'slug'           => (string) ($template->slug ?? ''),
                'is_mandatory'   => (bool) $template->is_mandatory,
                'is_auto_create' => (bool) $template->is_auto_create,
                'is_nested'      => (bool) $template->is_nested,
                'display_fields' => is_array($template->display_fields) ? array_values($template->display_fields) : [],
                'description'    => (string) ($template->description ?? ''),
            ],
            'fields'          => $fields,
            'parentTemplates' => $parentTemplates,
            'fieldTypes'      => $fieldTypes,
            'urls'            => [
                'list'       => \Backend::url('fun/wiki/templatevue'),
                'updateBase' => \Backend::url('fun/wiki/templatevue/update'),
                'create'     => \Backend::url('fun/wiki/templatevue/create'),
                'export'     => \Backend::url('fun/wiki/templatevue/export'),
            ],
        ];
    }

    public function onChangeEntryType()
    {
        $type = post('entry_type')
            ?? post('Template.type')
            ?? post('EntryRecord.content_group')
            ?? post('command');

        $id = post('id') ?? post('Template.id');
        $model = $id ? Template::find($id) : new Template();

        if ($model && in_array($type, ['article', 'node', 'variant'], true)) {
            $model->type = $type;
            if ($model->exists) {
                $model->save();
            }
        }

        $this->prepareVars($model);

        return [
            '#entryPrimaryTabs' => $this->makePartial('primary_tabs', [
                'formModel'    => $model,
                'template'     => $model,
                'templateData' => $this->prepareTemplatePayload($model),
            ])
        ];
    }

    public function onSave($recordId = null)
    {
        $templateData = post('Template', []) ?: post('template', []);
        $fieldsData = post('fields', []);
        if (empty($fieldsData) && post('fields_json')) {
            $fieldsData = json_decode((string) post('fields_json'), true) ?: [];
        }

        $id = $recordId ?: (!empty($templateData['id']) ? (int) $templateData['id'] : null);
        $model = $id ? Template::findOrFail($id) : new Template();

        $rules = [
            'name' => 'required',
            'code' => 'required|regex:/^[a-zA-Z0-9_\-]+$/|unique:fun_wiki_templates,code,' . ($model->id ?: 'NULL') . ',id',
        ];

        $validator = Validator::make($templateData, $rules, [
            'name.required' => 'Укажите название шаблона.',
            'code.required' => 'Укажите уникальный код шаблона.',
            'code.unique'   => 'Шаблон с таким кодом уже существует.',
            'code.regex'    => 'Код должен содержать только латинские буквы, цифры, дефис или подчеркивание.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        DB::transaction(function () use ($model, $templateData, $fieldsData) {
            $model->name = trim((string) ($templateData['name'] ?? ''));
            $model->code = trim((string) ($templateData['code'] ?? ''));
            $model->type = $templateData['type'] ?? post('entry_type') ?? ($model->type ?: 'article');
            $model->parent_id = !empty($templateData['parent']) ? (int) $templateData['parent'] : (!empty($templateData['parent_id']) ? (int) $templateData['parent_id'] : null);
            $model->slug = !empty($templateData['slug']) ? trim((string) $templateData['slug']) : null;
            $model->is_mandatory = !empty($templateData['is_mandatory']);
            $model->is_auto_create = !empty($templateData['is_auto_create']);
            $model->is_nested = !empty($templateData['is_nested']);

            $displayFields = $templateData['display_fields'] ?? [];
            if (is_string($displayFields)) {
                $displayFields = explode(',', $displayFields);
            }
            $model->display_fields = is_array($displayFields) ? array_values(array_filter(array_map('trim', $displayFields))) : [];
            $model->description = $templateData['description'] ?? null;
            $model->save();

            // Sync fields if provided
            if (is_array($fieldsData)) {
                $existingFieldIds = [];
                $sortOrder = 1;

                foreach ($fieldsData as $fData) {
                    $fieldId = !empty($fData['id']) ? (int) $fData['id'] : null;
                    $field = ($fieldId && $fieldId > 0) ? Field::where('template_id', $model->id)->find($fieldId) : null;
                    if (!$field) {
                        $field = new Field();
                        $field->template_id = $model->id;
                    }

                    $field->label = trim((string) ($fData['label'] ?? ''));
                    $field->name = trim((string) ($fData['name'] ?? ''));
                    $field->type = (string) ($fData['type'] ?? 'text');
                    $field->is_mandatory = !empty($fData['is_mandatory']);
                    $field->is_global = !empty($fData['is_global']);
                    $field->sort_order = $sortOrder++;
                    $field->depends_on_field = !empty($fData['depends_on_field']) ? (string) $fData['depends_on_field'] : null;
                    $field->depends_on_value = !empty($fData['depends_on_value']) ? (string) $fData['depends_on_value'] : null;
                    $field->config = is_array($fData['config'] ?? null) ? $fData['config'] : [];
                    $field->save();

                    $existingFieldIds[] = $field->id;
                }

                Field::where('template_id', $model->id)
                    ->where('is_parent_field', false)
                    ->whereNotIn('id', $existingFieldIds)
                    ->delete();
            }

            $model->syncAncestryFields();
        });

        Flash::success('Шаблон успешно сохранен');

        if (post('close')) {
            return Redirect::to(\Backend::url('fun/wiki/templatevue'));
        }

        return Redirect::to(\Backend::url('fun/wiki/templatevue/update/' . $model->id));
    }

    public function onDelete($recordId = null)
    {
        $id = $recordId ?: (post('id') ?? post('Template.id'));
        $model = Template::findOrFail($id);

        $model->fields()->delete();
        $model->delete();

        Flash::success('Шаблон успешно удален');

        return Redirect::to(\Backend::url('fun/wiki/templatevue'));
    }

    public function onResetStructure()
    {
        $model = new Template;
        $model->resetTreeNesting();
        $model->resetTreeOrphans();

        foreach (Template::cursor() as $template) {
            $template->syncAncestryFields();
        }

        Flash::success('Структура дерева шаблонов успешно перестроена');

        return $this->listRefresh();
    }

    public function export()
    {
        $service = new \Fun\Wiki\Services\TemplateTransferService();
        $data = $service->export();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'wiki_templates_vue_' . date('Y-m-d_His') . '.json';

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function onLoadImportPopup()
    {
        return $this->makePartial('$/fun/wiki/controllers/templates/_import_modal.php');
    }

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
                throw new ValidationException([
                    'import_json' => 'Загрузите JSON-файл или вставьте содержимое JSON в текстовое поле.'
                ]);
            }

            $truncate = (bool) post('truncate_existing', false);
            $result = $service->import($jsonContent, $truncate);

            Flash::success("Импорт завершен! Импортировано шаблонов: {$result['templates']}, полей: {$result['fields']}.");

            return Redirect::refresh();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Template import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            Flash::error('Ошибка импорта: ' . $e->getMessage());
            return Redirect::refresh();
        }
    }
}
