<?php declare(strict_types=1);

namespace Fun\Wiki\Services;

use Fun\Wiki\Models\Template;
use Illuminate\Support\Collection;

class TemplateInheritanceService
{
    /**
     * Получить полный эффективный набор полей шаблона с учетом наследования
     */
    public function getEffectiveFields(Template $template): Collection
    {
        $fields = $template->fields()->orderBy('sort_order')->get();

        // 1. Наследование глобальных полей (is_global) от предков по дереву NestedTree
        $ancestors = $template->getParents();
        if ($ancestors->isNotEmpty()) {
            $globalAncestorFields = collect();
            foreach ($ancestors as $ancestor) {
                $ancestorGlobals = $ancestor->fields()
                    ->where('is_global', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function ($f) {
                        $clone = clone $f;
                        $clone->is_inherited = true;
                        return $clone;
                    });
                $globalAncestorFields = $globalAncestorFields->concat($ancestorGlobals);
            }

            foreach ($globalAncestorFields as $globalField) {
                if (!$fields->contains('name', $globalField->name)) {
                    $fields->push($globalField);
                }
            }
        }

        // 2. Для Вариантов (type === 'variant') наследуем все поля расширяемого шаблона
        if ($template->type === 'variant' && $template->parent) {
            $parentFields = $this->getEffectiveFields($template->parent);
            foreach ($parentFields as $pField) {
                if (!$pField->is_parent_field && !$fields->contains('name', $pField->name)) {
                    $clone = clone $pField;
                    $clone->is_inherited = true;
                    $fields->push($clone);
                }
            }
        }

        return $fields;
    }
}
