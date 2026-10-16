<?php declare(strict_types=1);

namespace Fun\Wiki\Traits;

use Fun\Wiki\Models\Article;
use Fun\Wiki\Classes\FieldTypeRegistry;

/**
 * Trait HasTemplateSchema
 * Отвечает за поведение статьи на основе её шаблона:
 * - авто-присвоение типа (Node/Article/Variant)
 * - вычисление parent_id и base_article_id из родительских полей
 * - динамическое внедрение полей шаблона в форму статьи (filterFields)
 */
trait HasTemplateSchema
{
    public static function bootHasTemplateSchema(): void
    {
        static::saving(function (Article $article) {
            $article->applyTemplateSchema();
        });
    }

    /**
     * Применение правил шаблона перед сохранением статьи
     */
    public function applyTemplateSchema(): void
    {
        if (!$this->template) {
            if ($this->template_id) {
                $this->template = \Fun\Wiki\Models\Template::find($this->template_id);
            }
            if (!$this->template) {
                return;
            }
        }

        // 1. Автоматическая типизация сущности
        $this->type = $this->template->type;

        // 2. Разрешение родителя из значений родительских полей формы
        $this->resolveParentFromData();

        // 3. Автоматическое разрешение и достраивание цепочки промежуточных структурных узлов (Nodes)
        if ($this->type !== 'node') {
            try {
                $ancestryService = app(\Fun\Wiki\Services\AncestryService::class);
                $this->parent_id = $ancestryService->resolveIntermediateNode($this->template, $this->parent_id);
            } catch (\Throwable $e) {
                // Безопасный fallback при выполнении в моках или без DI
            }
        }
    }

    /**
     * Поиск родительской статьи в переданных полях данных
     */
    protected function resolveParentFromData(): void
    {
        $data = $this->data ?? [];
        if (empty($data)) {
            return;
        }

        // Ищем среди полей шаблона родительские поля связи
        $parentFields = $this->template->fields()
            ->where('is_parent_field', true)
            ->get();

        $resolvedParentId = null;

        foreach ($parentFields as $parentField) {
            if (!empty($data[$parentField->name])) {
                $resolvedParentId = (int) $data[$parentField->name];
            }
        }

        if ($resolvedParentId) {
            $this->parent_id = $resolvedParentId;

            // Для Варианта выбранный родитель также является базовой статьей
            if ($this->type === 'variant') {
                $this->base_article_id = $resolvedParentId;
            }
        }
    }

    /**
     * Конфигурация динамических полей шаблона для внедрения в форму статьи (Backend Form)
     *
     * @return array<string, array>
     */
    public function getTemplateFormFields(): array
    {
        $template = $this->template;
        if (!$template && $this->template_id) {
            $template = \Fun\Wiki\Models\Template::find($this->template_id);
        }

        if (!$template) {
            return [];
        }

        $effectiveFields = $template->effective_fields;
        $registry = FieldTypeRegistry::instance();

        $formFields = [];
        $hasParentFields = false;
        $hasDataFields = false;

        foreach ($effectiveFields as $fieldModel) {
            if ($fieldModel->is_parent_field) {
                $hasParentFields = true;
            } else {
                $hasDataFields = true;
            }
        }

        if ($hasParentFields) {
            $formFields['_section_ancestry'] = [
                'label'   => 'Иерархические связи (Дерево)',
                'type'    => 'section',
                'tab'     => 'Цепочка предков',
                'comment' => 'Определяет положение статьи в структуре и дереве NestedTree',
            ];
        }

        if ($hasDataFields) {
            $formFields['_section_data'] = [
                'label'   => 'Поля шаблона: ' . $template->name,
                'type'    => 'section',
                'tab'     => 'Поля данных',
                'comment' => 'Метаданные и характеристики сущности',
            ];
        }

        foreach ($effectiveFields as $fieldModel) {
            $driver = $registry->get($fieldModel->type) ?? $registry->get('text');
            if ($driver) {
                $tabName = $fieldModel->is_parent_field ? 'Цепочка предков' : 'Поля данных';
                $fieldConfig = $driver->getFormFieldConfig($fieldModel, $tabName);

                $formFields['data[' . $fieldModel->name . ']'] = $fieldConfig;
            }
        }

        return $formFields;
    }

    /**
     * Хук filterFields для работы с элементами формы
     */
    public function filterFields($fields, $context = null): void
    {
        // Базовый хук для управления уже объявленными полями формы
    }
}
