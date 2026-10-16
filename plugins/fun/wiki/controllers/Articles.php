<?php declare(strict_types=1);

namespace Fun\Wiki\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\VueComponents\Document;
use Backend\VueComponents\Popover;
use Backend\VueComponents\DropdownMenuButton;
use Fun\Wiki\Models\Article;
use Fun\Wiki\Models\Template;

class Articles extends Controller
{
    public $implement = [
        FormController::class,
        ListController::class,
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['fun.wiki.manage_articles'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Fun.Wiki', 'wiki', 'articles');
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
        $isCreate = $model ? !$model->exists : true;

        return [
            'isCreateAction' => $isCreate,
            'showEntryTypeSelector' => false,
            'contentGroup' => $type,
        ];
    }

    public function makeLangState(): array
    {
        return [
            'form_save'            => 'Сохранить',
            'form_save_close'      => 'Сохранить и закрыть',
            'form_delete'          => 'Удалить',
            'form_confirm_delete'  => 'Вы действительно хотите удалить эту статью?',
            'delete_entry_confirm' => 'Вы действительно хотите удалить эту статью?',
            'form_error'           => 'Ошибка формы',
        ];
    }

    /**
     * Динамическое расширение формы статьи полями выбранного шаблона
     */
    public function formExtendFields($form)
    {
        $model = $form->model;
        if (!$model instanceof Article) {
            return;
        }

        $postArticle = post('Article');
        $templateId = (is_array($postArticle) ? ($postArticle['template'] ?? $postArticle['template_id'] ?? null) : null)
            ?? post('Article.template')
            ?? post('Article.template_id')
            ?? post('template_id')
            ?? post('template')
            ?? get('template_id')
            ?? get('template')
            ?? $model->template_id;

        if ($templateId) {
            $model->template_id = (int) $templateId;
        }

        $customFields = $model->getTemplateFormFields();
        if (!empty($customFields)) {
            $form->addTabFields($customFields);
        }
    }

    /**
     * AJAX обновление формы при смене шаблона в выпадающем списке
     */
    public function onRefresh()
    {
        return $this->asExtension('FormController')->formGetWidget()->onRefresh();
    }

    /**
     * AJAX переключение типа сущности
     */
    public function onChangeEntryType()
    {
        $type = post('entry_type')
            ?? post('Article.type')
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
}
