<?php declare(strict_types=1);

namespace Fun\Wiki\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Fun\Wiki\Models\Template;

/**
 * MetaFields Form Widget
 * FormWidget для настройки отображения полей
 */
class MetaFields extends FormWidgetBase
{
    protected $defaultAlias = 'wikix_meta_fields';

    public ?string $from_label = null;
    public ?string $to_label = null;

    public function init(): void
    {
        $this->fillFromConfig([
            'from_label',
            'to_label',
        ]);
    }

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('metafields');
    }

    public function prepareVars(): void
    {
        $this->vars['field'] = $this->formField;
        $this->vars['name'] = $this->formField->getName();
        $this->vars['widget_name'] = str_slug($this->formField->fieldName, '_');
        $this->vars['widget_id'] = str_replace('-', '_', $this->getId());
        $this->vars['model'] = $this->model;
        $this->vars['handlerUrl'] = $this->getEventHandler('onSetVisible');

        $fromLabel = $this->from_label
            ?? $this->getConfig('from_label')
            ?? $this->formField->getConfig('from_label')
            ?? 'Data fields';

        $toLabel = $this->to_label
            ?? $this->getConfig('to_label')
            ?? $this->formField->getConfig('to_label')
            ?? 'MetaData';

        $this->vars['from_label'] = trans((string) $fromLabel);
        $this->vars['to_label']   = trans((string) $toLabel);

        $allFields = collect();
        if ($this->model instanceof Template) {
            $allFields = $this->model->effective_fields ?? $this->model->fields ?? collect();
        }

        $rawDisplayFields = $this->getLoadValue();
        if (is_null($rawDisplayFields) && $this->model instanceof Template) {
            $rawDisplayFields = $this->model->display_fields;
        }

        if (is_string($rawDisplayFields)) {
            $decoded = json_decode($rawDisplayFields, true);
            $rawDisplayFields = is_array($decoded) ? $decoded : [];
        }

        $displayFieldNames = is_array($rawDisplayFields) ? array_values(array_filter($rawDisplayFields)) : [];
        $this->vars['displayFieldNames'] = $displayFieldNames;

        $fieldsByCode = [];
        foreach ($allFields as $field) {
            $fieldsByCode[$field->name] = $field;
        }

        $visibleFields = [];
        foreach ($displayFieldNames as $name) {
            if (isset($fieldsByCode[$name])) {
                $visibleFields[] = $fieldsByCode[$name];
                unset($fieldsByCode[$name]);
            }
        }

        $availableFields = array_values($fieldsByCode);

        $this->vars['visibleFields']   = $visibleFields;
        $this->vars['availableFields'] = $availableFields;
    }

    public function loadAssets(): void
    {
        $this->addCss('/plugins/fun/wiki/formwidgets/metafields/assets/css/metafields.css');
        $this->addJs('/plugins/fun/wiki/formwidgets/metafields/assets/js/sortable.min.js');
        $this->addJs('/plugins/fun/wiki/formwidgets/metafields/assets/js/metafields.js');
    }

    public function getSaveValue($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $value)));
    }

    /**
     * AJAX обработчик сохранения видимых полей (как в WikiX onSetVisible)
     */
    public function onSetVisible()
    {
        $data = input('value') ?? input('display_fields') ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }

        $cleaned = is_array($data) ? array_values(array_filter(array_map('trim', $data))) : [];

        if ($this->model instanceof Template && $this->model->exists) {
            $this->model->display_fields = $cleaned;
            $this->model->save();
        }

        return ['status' => 'success', 'value' => $cleaned];
    }
}

