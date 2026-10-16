<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

class ImageFieldType extends BaseFieldType
{
    public function getCode(): string
    {
        return 'image';
    }

    public function getLabel(): string
    {
        return 'Изображение / Медиа';
    }

    public function getDescription(): string
    {
        return 'Загрузка или выбор изображения из медиа-хранилища';
    }

    public function getIcon(): string
    {
        return 'icon-picture-o';
    }

    public function getFormFieldConfig(\Fun\Wiki\Models\Field $field, ?string $tab = 'Поля данных'): array
    {
        $config = parent::getFormFieldConfig($field, $tab);
        $config['type'] = 'mediafinder';
        $config['mode'] = 'image';
        $config['imageWidth'] = 200;
        $config['imageHeight'] = 200;

        if (!empty($field->config['as_gallery'])) {
            $config['maxItems'] = 20;
            $config['comment'] = 'Галерея изображений';
        }

        if (!empty($field->config['as_cover'])) {
            $config['comment'] = trim(($config['comment'] ?? '') . ' (Основная обложка)');
        }

        return $config;
    }
}
