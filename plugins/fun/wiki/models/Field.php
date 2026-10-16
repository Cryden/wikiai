<?php declare(strict_types=1);

namespace Fun\Wiki\Models;

use Model;
use Fun\Wiki\Classes\FieldTypeRegistry;

class Field extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

    public $table = 'fun_wiki_fields';

    protected $fillable = [
        'template_id',
        'label',
        'name',
        'type',
        'is_mandatory',
        'is_global',
        'is_parent_field',
        'is_inherited',
        'depends_on_field',
        'depends_on_value',
        'sort_order',
        'config'
    ];

    protected $casts = [
        'is_mandatory'    => 'boolean',
        'is_global'       => 'boolean',
        'is_parent_field' => 'boolean',
        'is_inherited'    => 'boolean',
    ];

    protected $jsonable = ['config'];

    public $rules = [
        'label' => 'required',
        'name'  => 'required'
    ];

    public function beforeValidate(): void
    {
        if ($this->template_id) {
            $this->rules['name'] = 'required|regex:/^[a-zA-Z0-9_\-]+$/|unique:fun_wiki_fields,name,' . $this->id . ',id,template_id,' . $this->template_id;
        }
    }

    public $belongsTo = [
        'template' => [Template::class, 'key' => 'template_id']
    ];

    /**
     * Динамический список доступных типов полей из реестра
     */
    public function getTypeOptions(): array
    {
        return FieldTypeRegistry::instance()->getOptions();
    }

    /**
     * Список шаблонов для полей типа relation и parent
     */
    public function getTemplateOptions(): array
    {
        return Template::pluck('name', 'id')->all();
    }

    /**
     * Список полей текущего шаблона для настройки зависимости (Depends On / Trigger)
     */
    public function getAvailableDependsOnFields(): array
    {
        $templateId = $this->template_id ?? post('Field.template_id') ?? get('template_id');
        if (!$templateId) {
            return [];
        }

        return Field::where('template_id', $templateId)
            ->where('id', '!=', $this->id ?? 0)
            ->pluck('label', 'name')
            ->all();
    }

    /**
     * Читаемое имя типа данных для колонок списков
     */
    public function getTypeNameAttribute(): string
    {
        $registry = FieldTypeRegistry::instance();
        return $registry->get($this->type ?? '')?->getLabel() ?? $this->type ?? '';
    }

    public function beforeSave(): void
    {
        if ($this->type === 'parent') {
            $this->is_parent_field = true;
        }
    }
}
