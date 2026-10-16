<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

use Fun\Wiki\Models\Field;
use Fun\Wiki\Models\Article;

class RelationFieldType extends BaseFieldType
{
    public function getCode(): string
    {
        return 'relation';
    }

    public function getLabel(): string
    {
        return 'Связь со статьей (Сущность)';
    }

    public function getDescription(): string
    {
        return 'Связь с другой статьей выбранного шаблона';
    }

    public function getIcon(): string
    {
        return 'icon-link';
    }

    public function getFormFieldConfig(Field $field, ?string $tab = 'Поля данных'): array
    {
        $targetTemplateId = $field->config['target_template_id'] ?? $field->config['target_template'] ?? null;

        $options = [];
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
        $isMultiple = !empty($field->config['is_multiple']);

        if ($isMultiple) {
            $config['type'] = 'taglist';
            $config['mode'] = 'array';
            $config['options'] = $options;
        } else {
            $config['type'] = 'dropdown';
            $config['emptyOption'] = '-- Выберите сущность --';
            $config['options'] = $options;
        }

        if ($field->depends_on_field) {
            $config['dependsOn'] = 'data[' . $field->depends_on_field . ']';
        }

        return $config;
    }
}
