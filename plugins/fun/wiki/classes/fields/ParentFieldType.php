<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

use Fun\Wiki\Models\Field;
use Fun\Wiki\Models\Article;

class ParentFieldType extends BaseFieldType
{
    public function getCode(): string
    {
        return 'parent';
    }

    public function getLabel(): string
    {
        return 'Родительское поле (Цепочка предков)';
    }

    public function getDescription(): string
    {
        return 'Иерархическая связь для вычисления дерева NestedTree';
    }

    public function getIcon(): string
    {
        return 'icon-sitemap';
    }

    public function getFormFieldConfig(Field $field, ?string $tab = 'Цепочка предков'): array
    {
        $targetTemplateId = $field->config['target_template_id'] ?? $field->config['target_template'] ?? null;

        $query = Article::query();
        if ($targetTemplateId) {
            $query->where('template_id', $targetTemplateId);
        }

        if ($field->depends_on_field) {
            $parentVal = post('Article.data.' . $field->depends_on_field)
                ?? (is_array(post('Article.data')) ? (post('Article.data')[$field->depends_on_field] ?? null) : null)
                ?? (is_array(post('Article')) ? (post('Article')['data'][$field->depends_on_field] ?? null) : null);

            if ($parentVal) {
                $query->where('parent_id', $parentVal);
            }
        }

        $options = $query->orderBy('title')->lists('title', 'id');

        $config = parent::getFormFieldConfig($field, $tab);
        $config['type'] = 'dropdown';
        $config['emptyOption'] = '-- Выберите родителя --';
        $config['options'] = $options;
        $config['span'] = 'left';
        $config['comment'] = 'Определяет положение в дереве NestedTree';

        // Использование нативных Hot Controls октября: dependsOn
        if ($field->depends_on_field) {
            $config['dependsOn'] = 'data[' . $field->depends_on_field . ']';
        }

        return $config;
    }
}
