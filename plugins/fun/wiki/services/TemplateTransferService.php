<?php declare(strict_types=1);

namespace Fun\Wiki\Services;

use Fun\Wiki\Models\Template;
use Fun\Wiki\Models\Field;
use Illuminate\Support\Str;
use October\Rain\Exception\ValidationException;
use DB;
use Schema;

class TemplateTransferService
{
    /**
     * Экспорт всех шаблонов и их полей в массив
     */
    public function export(): array
    {
        $templates = Template::with(['fields'])->orderBy('nest_left')->get();
        $templateCodeMap = Template::pluck('code', 'id')->all();
        $result = [];

        foreach ($templates as $template) {
            $parentCode = $template->parent?->code;

            // Формируем поля шаблона
            $fieldsData = [];
            foreach ($template->fields as $field) {
                $targetTemplateId = $field->config['target_template_id'] ?? null;
                $targetTemplateCode = $targetTemplateId ? ($templateCodeMap[$targetTemplateId] ?? null) : null;

                $fieldsData[] = [
                    'field_label'            => $field->label,
                    'field_name'             => $field->name,
                    'field_required'         => $field->is_mandatory ? '1' : '0',
                    'is_global'              => $field->is_global ? '1' : '0',
                    '_group'                 => $field->type,
                    'field_relation_model'   => $targetTemplateCode,
                    'field_multiple'         => !empty($field->config['is_multiple']) ? '1' : '0',
                    'field_is_auto_create'   => !empty($field->config['is_auto_create']) ? '1' : '0',
                    'field_as_gallery'       => !empty($field->config['as_gallery']) ? '1' : '0',
                    'field_as_cover'         => !empty($field->config['as_cover']) ? '1' : '0',
                    'field_group'            => $field->config['field_group'] ?? null,
                    'field_group_name'       => $field->config['field_group_name'] ?? null,
                    'field_depends_on'       => !empty($field->depends_on_field) ? '1' : '0',
                    'field_depends_on_field' => $field->depends_on_field ?? '',
                ];
            }

            // Формируем view_fields ассоциативным JSON
            $viewFieldsMap = [];
            if (is_array($template->display_fields)) {
                foreach ($template->display_fields as $fieldCode) {
                    $fieldObj = $template->fields->where('name', $fieldCode)->first();
                    $viewFieldsMap[$fieldCode] = $fieldObj?->label ?? $fieldCode;
                }
            }

            $result[] = [
                'name'           => $template->name,
                'code'           => $template->code,
                'is_node'        => $template->type === 'node' ? 1 : 0,
                'slug'           => $template->slug ?? '',
                'is_required'    => $template->is_mandatory ? 1 : 0,
                'is_nested'      => $template->is_nested ? 1 : 0,
                'is_auto_create' => $template->is_auto_create ? 1 : 0,
                'fields'         => !empty($fieldsData) ? $fieldsData : null,
                'view_fields'    => !empty($viewFieldsMap) ? json_encode($viewFieldsMap, JSON_UNESCAPED_UNICODE) : '{}',
                'parent_id'      => $parentCode,
            ];
        }

        return $result;
    }

    /**
     * Импорт шаблонов из массива или JSON-строки
     *
     * @param array|string $data
     * @param bool $truncate
     * @return array ['templates' => int, 'fields' => int]
     */
    public function import(array|string $data, bool $truncate = false): array
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (!is_array($decoded)) {
                throw new \InvalidArgumentException('Некорректный формат JSON для импорта.');
            }
            $data = $decoded;
        }

        if (empty($data)) {
            throw new \InvalidArgumentException('Передан пустой список шаблонов.');
        }

        Template::$skipAncestrySync = true;

        try {
            return DB::transaction(function () use ($data, $truncate) {
                if ($truncate) {
                    Schema::disableForeignKeyConstraints();
                    try {
                        Field::truncate();
                        Template::truncate();
                    } finally {
                        Schema::enableForeignKeyConstraints();
                    }
                }

                $importedTemplatesCount = 0;
                $importedFieldsCount = 0;

                // 1. Создаем или обновляем сами шаблоны без parent_id
                foreach ($data as $item) {
                    if (empty($item['code']) || empty($item['name'])) {
                        continue;
                    }

                    $isNode = !empty($item['is_node']);
                    $type = $isNode ? 'node' : ($item['type'] ?? 'article');

                    // Разбор view_fields / display_fields
                    $displayFields = [];
                    if (!empty($item['display_fields']) && is_array($item['display_fields'])) {
                        $displayFields = $item['display_fields'];
                    } elseif (!empty($item['view_fields'])) {
                        $vf = is_string($item['view_fields']) ? json_decode($item['view_fields'], true) : $item['view_fields'];
                        if (is_array($vf)) {
                            $isAssoc = array_keys($vf) !== range(0, count($vf) - 1);
                            $displayFields = $isAssoc ? array_keys($vf) : array_values($vf);
                        }
                    }

                    $template = Template::firstOrNew(['code' => $item['code']]);
                    $template->name = $item['name'];
                    $template->type = $type;
                    $template->slug = !empty($item['slug']) ? $item['slug'] : null;
                    $template->is_mandatory = !empty($item['is_required']) || !empty($item['is_mandatory']);
                    $template->is_nested = !empty($item['is_nested']);
                    $template->is_auto_create = !empty($item['is_auto_create']);
                    $template->display_fields = $displayFields;
                    $template->save();

                    $importedTemplatesCount++;
                }

                // 2. Выстраиваем parent_id и иерархию дерева
                foreach ($data as $item) {
                    if (empty($item['code'])) {
                        continue;
                    }

                    $template = Template::where('code', $item['code'])->first();
                    if (!$template) {
                        continue;
                    }

                    $parentCode = $item['parent_id'] ?? null;
                    if ($parentCode) {
                        $parentTemplate = Template::where('code', $parentCode)->first();
                        $template->parent_id = $parentTemplate?->id;
                    } else {
                        $template->parent_id = null;
                    }

                    $template->save();
                }

                // Перестраиваем структуру NestedTree (в October CMS NestedTree trait - это resetTreeNesting)
                (new Template)->resetTreeNesting();

                // 3. Создаем поля шаблонов
                foreach ($data as $item) {
                    if (empty($item['code']) || empty($item['fields']) || !is_array($item['fields'])) {
                        continue;
                    }

                    $template = Template::where('code', $item['code'])->first();
                    if (!$template) {
                        continue;
                    }

                    $sortOrder = 0;
                    foreach ($item['fields'] as $fieldItem) {
                        $label = $fieldItem['field_label'] ?? $fieldItem['label'] ?? '';
                        $name = $fieldItem['field_name'] ?? $fieldItem['name'] ?? '';
                        if (empty($name)) {
                            $name = Str::slug($label, '_');
                        }
                        if (empty($name)) {
                            continue;
                        }

                        $rawGroup = $fieldItem['_group'] ?? $fieldItem['group'] ?? $fieldItem['type'] ?? 'text';
                        $type = match ($rawGroup) {
                            'tags' => 'tag',
                            'relation_list' => 'relation',
                            default => $rawGroup,
                        };

                        $targetTemplateCode = $fieldItem['field_relation_model'] ?? null;
                        $targetTemplateId = null;
                        if ($targetTemplateCode) {
                            $targetTemplateId = Template::where('code', $targetTemplateCode)->value('id');
                            if (!$targetTemplateId && is_numeric($targetTemplateCode)) {
                                $targetTemplateId = Template::where('id', (int)$targetTemplateCode)->value('id');
                            }
                        }

                        $config = [
                            'target_template_id' => $targetTemplateId,
                            'target_template'    => $targetTemplateId,
                            'is_multiple'        => !empty($fieldItem['field_multiple']) || $rawGroup === 'relation_list',
                            'is_auto_create'     => !empty($fieldItem['field_is_auto_create']),
                            'is_nested'          => !empty($fieldItem['field_nested']),
                            'as_gallery'         => !empty($fieldItem['field_as_gallery']),
                            'as_cover'           => !empty($fieldItem['field_as_cover']),
                            'field_group'        => $fieldItem['field_group'] ?? null,
                            'field_group_name'   => $fieldItem['field_group_name'] ?? null,
                        ];

                        $isParentField = ($type === 'parent');

                        $fieldModel = Field::firstOrNew([
                            'template_id' => $template->id,
                            'name'        => $name,
                        ]);

                        $fieldModel->label            = $label;
                        $fieldModel->type             = $type;
                        $fieldModel->is_mandatory     = !empty($fieldItem['field_required']) || !empty($fieldItem['is_mandatory']);
                        $fieldModel->is_global        = !empty($fieldItem['is_global']);
                        $fieldModel->is_parent_field  = $isParentField;
                        $fieldModel->depends_on_field = !empty($fieldItem['field_depends_on_field']) ? $fieldItem['field_depends_on_field'] : null;
                        $fieldModel->sort_order       = $sortOrder++;
                        $fieldModel->config           = array_filter($config, fn($v) => $v !== null && $v !== '');
                        $fieldModel->save();

                        $importedFieldsCount++;
                    }
                }

                // 4. Синхронизируем и актуализируем цепочки родительских полей
                Template::$skipAncestrySync = false;
                foreach (Template::all() as $tpl) {
                    $tpl->syncAncestryFields();
                }

                return [
                    'templates' => $importedTemplatesCount,
                    'fields'    => $importedFieldsCount,
                ];
            });
        } finally {
            Template::$skipAncestrySync = false;
        }
    }
}
