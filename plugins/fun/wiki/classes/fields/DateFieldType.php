<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

class DateFieldType extends BaseFieldType
{
    public function getCode(): string
    {
        return 'date';
    }

    public function getLabel(): string
    {
        return 'Дата / Временная отметка';
    }

    public function getDescription(): string
    {
        return 'Выбор календарной даты и времени';
    }

    public function getIcon(): string
    {
        return 'icon-calendar';
    }

    public function getFieldConfig(): array
    {
        return [
            'type' => 'datepicker',
            'mode' => 'date',
            'span' => 'left'
        ];
    }
}
