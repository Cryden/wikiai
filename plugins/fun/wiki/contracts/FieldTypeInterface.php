<?php declare(strict_types=1);

namespace Fun\Wiki\Contracts;

use Fun\Wiki\Models\Field;

interface FieldTypeInterface
{
    /**
     * Уникальный строковый код типа поля (например, 'text', 'date', 'geopoint')
     */
    public function getCode(): string;

    /**
     * Отображаемое название типа в Конструкторе шаблона
     */
    public function getLabel(): string;

    /**
     * Краткое описание назначения типа поля
     */
    public function getDescription(): string;

    /**
     * CSS-класс иконки типа поля (например, 'icon-font', 'icon-calendar')
     */
    public function getIcon(): string;

    /**
     * Конфигурация формы настройки поля в RelationManager
     */
    public function getFieldConfig(): array;

    /**
     * Рендеринг виджета ввода значения в форме статьи
     */
    public function renderInput(Field $field, mixed $value): string;

    /**
     * Рендеринг значения поля для публичного отображения в инфобоксе/статье
     */
    public function renderDisplay(Field $field, mixed $value): string;

    /**
     * Валидация значения поля
     */
    public function validate(mixed $value, array $rules = []): bool;
}
