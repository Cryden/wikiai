<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

class TagFieldType extends BaseFieldType
{
    public function getCode(): string
    {
        return 'tags';
    }

    public function getLabel(): string
    {
        return 'Тэги / Метки';
    }

    public function getDescription(): string
    {
        return 'Набор меток и тегов для гибкой категоризации';
    }

    public function getIcon(): string
    {
        return 'icon-tags';
    }

    public function getFormFieldConfig(\Fun\Wiki\Models\Field $field, ?string $tab = 'Поля данных'): array
    {
        $config = parent::getFormFieldConfig($field, $tab);
        $config['type'] = 'taglist';
        $config['mode'] = 'string';
        $config['separator'] = 'comma';
        $config['customTags'] = !isset($field->config['is_auto_create']) || !empty($field->config['is_auto_create']);

        if (!empty($field->config['field_group_name'])) {
            $config['comment'] = 'Группа тегов: ' . $field->config['field_group_name'];
        }

        if (isset($field->config['is_multiple']) && !$field->config['is_multiple']) {
            $config['maxItems'] = 1;
        }

        return $config;
    }
}
