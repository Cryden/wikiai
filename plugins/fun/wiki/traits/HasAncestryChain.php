<?php declare(strict_types=1);

namespace Fun\Wiki\Traits;

/**
 * Trait HasAncestryChain
 * Предоставляет методы для генерации хлебных крошек и полных путей в дереве
 */
trait HasAncestryChain
{
    /**
     * Построение упорядоченной цепочки хлебных крошек от корня до текущего узла
     */
    public function getBreadcrumbs(): array
    {
        $trail = [];
        $current = $this;

        while ($current) {
            array_unshift($trail, [
                'id'    => $current->id,
                'title' => $current->title ?? $current->name,
                'slug'  => $current->slug ?? $current->code,
                'type'  => $current->type,
            ]);
            $current = $current->parent;
        }

        return $trail;
    }

    /**
     * Формирование полного URL-пути (URI) сквозь все узлы дерева
     */
    public function getFullSlugPath(): string
    {
        $crumbs = $this->getBreadcrumbs();
        $slugs = array_column($crumbs, 'slug');

        return implode('/', array_filter($slugs));
    }
}
