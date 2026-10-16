<?php declare(strict_types=1);

namespace Fun\Wiki\Traits;

/**
 * Trait HasDisplayFields
 * Фильтрация данных статьи для формирования публичного инфобокса
 */
trait HasDisplayFields
{
    /**
     * Возвращает только те пары ключ-значение, которые отмечены в display_fields шаблона
     */
    public function getDisplayData(): array
    {
        if (!$this->template_id && !$this->relationLoaded('template')) {
            return [];
        }

        $template = $this->template;
        if (!$template) {
            return [];
        }

        $allowedKeys = $template->display_fields ?? [];
        $rawPayload  = $this->data ?? [];

        if (empty($allowedKeys)) {
            return $rawPayload;
        }

        $result = [];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $rawPayload)) {
                $result[$key] = $rawPayload[$key];
            }
        }

        return $result;
    }
}
