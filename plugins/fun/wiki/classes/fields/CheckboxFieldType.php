<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

use Fun\Wiki\Models\Field;

class CheckboxFieldType extends BaseFieldType
{
    public function getCode(): string
    {
        return 'checkbox';
    }

    public function getLabel(): string
    {
        return 'Чекбокс / Переключатель (Да/Нет)';
    }

    public function getDescription(): string
    {
        return 'Логическое значение Да/Нет (переключатель)';
    }

    public function getIcon(): string
    {
        return 'icon-check-square-o';
    }

    public function getFieldConfig(): array
    {
        return [
            'type' => 'switch',
            'span' => 'left'
        ];
    }

    public function getTriggerOptions(Field $field): array
    {
        return [
            'type'    => 'dropdown',
            'options' => [
                '1' => 'Включено (Checked)',
                '0' => 'Выключено (Unchecked)'
            ]
        ];
    }
}
