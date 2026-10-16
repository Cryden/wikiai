<?php declare(strict_types=1);

namespace Fun\Wiki\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\NestedTree;
use Fun\Wiki\Traits\HasParentFields;
use Fun\Wiki\Services\TemplateInheritanceService;
use Illuminate\Support\Collection;

/**
 * Class Template
 * Модель шаблона базы знаний
 */
class Template extends Model
{
    use Validation;
    use NestedTree;
    use HasParentFields;

    public $table = 'fun_wiki_templates';

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'parent_id',
        'slug',
        'is_mandatory',
        'is_auto_create',
        'is_nested',
        'display_fields'
    ];

    protected $casts = [
        'is_mandatory'   => 'boolean',
        'is_auto_create' => 'boolean',
        'is_nested'      => 'boolean',
    ];

    protected $jsonable = ['display_fields'];

    public $rules = [
        'name' => 'required',
        'code' => 'required|unique:fun_wiki_templates,code'
    ];

    public $hasMany = [
        'fields'   => [Field::class, 'key' => 'template_id', 'order' => 'sort_order asc'],
        'articles' => [Article::class, 'key' => 'template_id']
    ];

    /**
     * Алиас для родительского шаблона
     */
    public function getParentTemplateAttribute(): ?Template
    {
        return $this->parent;
    }

    /**
     * Получение полного набора полей (включая наследование для вариантов)
     */
    public function getEffectiveFieldsAttribute(): Collection
    {
        return app(TemplateInheritanceService::class)->getEffectiveFields($this);
    }

    /**
     * Динамическая настройка полей формы в зависимости от типа шаблона
     */
    public function filterFields($fields, $context = null): void
    {
        $type = $this->type ?? 'article';

        if ($type === 'node') {
            if (isset($fields->fields)) {
                $fields->fields->hidden = true;
            }
            if (isset($fields->display_fields)) {
                $fields->display_fields->hidden = true;
            }
            if (isset($fields->is_nested)) {
                $fields->is_nested->hidden = true;
            }
            if (isset($fields->is_auto_create)) {
                $fields->is_auto_create->hidden = true;
            }
            if (isset($fields->is_mandatory)) {
                $fields->is_mandatory->hidden = true;
            }
        } elseif ($type === 'variant') {
            if (isset($fields->parent)) {
                $fields->parent->label = 'Базовый шаблон';
                $fields->parent->comment = 'Шаблон, от которого наследуются поля';
            }
            if (isset($fields->is_nested)) {
                $fields->is_nested->hidden = true;
            }
            if (isset($fields->slug)) {
                $fields->slug->hidden = true;
            }
        }
    }

    /**
     * Опции для выпадающего списка отображаемых полей (теглиста)
     */
    public function getDisplayFieldsOptions(): array
    {
        return $this->effective_fields->pluck('label', 'name')->all();
    }
}
