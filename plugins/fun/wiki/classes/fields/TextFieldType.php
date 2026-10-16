<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

class TextFieldType extends BaseFieldType
{
    public function getCode(): string
    {
        return 'text';
    }

    public function getLabel(): string
    {
        return 'Текст (Строка)';
    }

    public function getDescription(): string
    {
        return 'Однострочное текстовое поле для коротких значений';
    }

    public function getIcon(): string
    {
        return 'icon-font';
    }
}
