<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

class NumberFieldType extends BaseFieldType
{
    public function getCode(): string
    {
        return 'number';
    }

    public function getLabel(): string
    {
        return 'Число (Количество, вес, год)';
    }

    public function getDescription(): string
    {
        return 'Числовое значение (целое или дробное)';
    }

    public function getIcon(): string
    {
        return 'icon-calculator';
    }

    public function getFieldConfig(): array
    {
        return [
            'type' => 'number',
            'span' => 'left'
        ];
    }
}
