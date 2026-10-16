<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

use Fun\Wiki\Contracts\FieldTypeInterface;
use Fun\Wiki\Models\Field;

abstract class BaseFieldType implements FieldTypeInterface
{
    public function getDescription(): string
    {
        return '';
    }

    public function getIcon(): string
    {
        return 'icon-cog';
    }

    public function getFieldConfig(): array
    {
        return [
            'type' => 'text'
        ];
    }

    /**
     * Конфигурация поля для динамического добавления в форму статьи (filterFields)
     */
    public function getFormFieldConfig(Field $field, ?string $tab = 'Поля данных'): array
    {
        $config = [
            'label'    => $field->label,
            'tab'      => $tab,
            'required' => (bool) $field->is_mandatory,
            'span'     => 'auto'
        ];

        // Поддержка условий видимости (Depends On / Trigger)
        if ($field->depends_on_field) {
            $config['trigger'] = [
                'action'    => 'show',
                'field'     => 'data[' . $field->depends_on_field . ']',
                'condition' => 'value[' . ($field->depends_on_value ?? '') . ']'
            ];
        }

        return array_merge($config, $this->getFieldConfig());
    }

    /**
     * Опции для настройки триггера зависимости
     */
    public function getTriggerOptions(Field $field): array
    {
        return [
            'type' => 'text'
        ];
    }

    public function renderInput(Field $field, mixed $value): string
    {
        return (string) $value;
    }

    public function renderDisplay(Field $field, mixed $value): string
    {
        return e((string) $value);
    }

    public function validate(mixed $value, array $rules = []): bool
    {
        return true;
    }
}
