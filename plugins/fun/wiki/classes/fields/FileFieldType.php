<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

class FileFieldType extends BaseFieldType
{
    public function getCode(): string
    {
        return 'file';
    }

    public function getLabel(): string
    {
        return 'Файл / Документ';
    }

    public function getDescription(): string
    {
        return 'Загрузка и прикрепление произвольных файлов и документов';
    }

    public function getIcon(): string
    {
        return 'icon-file-o';
    }

    public function getFormFieldConfig(\Fun\Wiki\Models\Field $field, ?string $tab = 'Поля данных'): array
    {
        $config = parent::getFormFieldConfig($field, $tab);
        $config['type'] = 'mediafinder';
        $config['mode'] = 'file';

        if (!empty($field->config['is_multiple'])) {
            $config['maxItems'] = 20;
            $config['comment'] = 'Множественная загрузка файлов';
        }

        return $config;
    }
}
