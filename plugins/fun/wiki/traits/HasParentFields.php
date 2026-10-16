<?php declare(strict_types=1);

namespace Fun\Wiki\Traits;

use Fun\Wiki\Models\Template;

/**
 * Trait HasParentFields
 * Отвечает за автоматический обход цепочки предков через NestedTree
 * и поддержание актуальности родительских полей (Parent Fields).
 */
trait HasParentFields
{
    public static bool $skipAncestrySync = false;

    public static function bootHasParentFields(): void
    {
        static::saved(function (Template $model) {
            if (!static::$skipAncestrySync) {
                $model->syncAncestryFields();
            }
        });
    }

    /**
     * Синхронизация полей всей цепочки предков (getParentsAndSelf)
     */
    public function syncAncestryFields(): void
    {
        // Если шаблон самовложенный, цепочка предков начинается с него самого
        // Иначе — с родительского шаблона parent
        $ancestors = collect();
        if ($this->is_nested) {
            $ancestors = $this->getParentsAndSelf();
        } elseif ($this->parent_id && $this->parent) {
            $ancestors = $this->parent->getParentsAndSelf();
        }

        if ($ancestors->isEmpty()) {
            // Удаляем родительские поля, если шаблон находится в корне и не самовложенный
            $this->fields()->where('is_parent_field', true)->delete();
            $this->syncChildrenAncestryFields();
            return;
        }

        $activeFieldIds = [];
        $dependsOnFieldName = null;

        foreach ($ancestors as $ancestor) {
            // Структурные узлы (Node) не требуют полей ввода сущности в форме статьи
            if ($ancestor->type === 'node') {
                continue;
            }

            // Ищем существующее поле предка
            $parentField = $this->fields()
                ->where('is_parent_field', true)
                ->where(function ($q) use ($ancestor) {
                    $q->where('name', $ancestor->code)
                      ->orWhere('name', 'parent_' . $ancestor->code)
                      ->orWhere('name', \Illuminate\Support\Str::kebab($ancestor->code))
                      ->orWhere('name', \Illuminate\Support\Str::snake($ancestor->code));
                })
                ->first();

            if (!$parentField) {
                $parentField = $this->fields()
                    ->where('is_parent_field', true)
                    ->get()
                    ->first(fn($f) => ($f->config['target_template_id'] ?? null) == $ancestor->id);
            }

            $fieldName = $parentField ? $parentField->name : $ancestor->code;

            $fieldAttributes = [
                'label'            => $ancestor->name,
                'name'             => $fieldName,
                'type'             => 'parent',
                'is_mandatory'     => (bool) $ancestor->is_mandatory,
                'is_parent_field'  => true,
                'is_inherited'     => false,
                'depends_on_field' => $dependsOnFieldName,
                'config'           => [
                    'target_template_id' => $ancestor->id,
                    'is_auto_create'     => (bool) $ancestor->is_auto_create,
                    'is_nested'          => (bool) $ancestor->is_nested
                ]
            ];

            if (!$parentField) {
                $parentField = $this->fields()->create($fieldAttributes);
            } else {
                $parentField->update($fieldAttributes);
            }

            $activeFieldIds[] = $parentField->id;
            $dependsOnFieldName = $fieldName;
        }

        // Удаляем устаревшие родительские поля, которых больше нет в цепочке предков
        $this->fields()
            ->where('is_parent_field', true)
            ->whereNotIn('id', $activeFieldIds)
            ->delete();

        // Каскадно синхронизируем дочерние шаблоны
        $this->syncChildrenAncestryFields();
    }

    /**
     * Каскадная синхронизация цепочки предков у всех дочерних шаблонов
     */
    protected function syncChildrenAncestryFields(): void
    {
        if ($this->relationLoaded('children') || $this->children()->exists()) {
            foreach ($this->children as $child) {
                $child->syncAncestryFields();
            }
        }
    }
}
