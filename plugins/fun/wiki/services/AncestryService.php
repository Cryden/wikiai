<?php declare(strict_types=1);

namespace Fun\Wiki\Services;

use Fun\Wiki\Contracts\AncestryResolverInterface;
use Fun\Wiki\Models\Template;
use Fun\Wiki\Models\Article;
use Illuminate\Support\Str;

class AncestryService implements AncestryResolverInterface
{
    /**
     * Создание статьи с автоматическим разрешением и достраиванием цепочки предков
     */
    public function resolveAndCreate(Template $targetTemplate, array $payload, ?int $explicitParentId = null): Article
    {
        $parentId = $explicitParentId ?? ($payload['parent_id'] ?? null);
        $title    = $payload['title'];
        $slug     = $payload['slug'] ?? Str::slug($title);
        $data     = $payload['data'] ?? [];
        $content  = $payload['content'] ?? '';
        $baseId   = $payload['base_article_id'] ?? null;

        // Если это Вариант и указана базовая статья, родителем становится базовая статья
        if ($targetTemplate->type === 'variant' && $baseId) {
            $baseArticle = Article::findOrFail($baseId);
            $parentId = $baseArticle->id;
        }

        // Проверка: нужен ли обязательный промежуточный структурный Узел (Node)
        $effectiveParentId = $this->resolveIntermediateNode($targetTemplate, $parentId);

        return Article::create([
            'title'           => $title,
            'slug'            => $slug,
            'template_id'     => $targetTemplate->id,
            'parent_id'       => $effectiveParentId,
            'base_article_id' => $baseId,
            'type'            => $targetTemplate->type,
            'data'            => $data,
            'content'         => $content,
            'status'          => $payload['status'] ?? 'published'
        ]);
    }

    /**
     * Разрешение и дедупликация цепочки промежуточных структурных узлов (Nodes)
     *
     * @param Template $childTemplate Шаблон статьи, для которой определяется родитель
     * @param int|null $parentId Исходный ID родительской статьи (или null)
     * @return int|null Итоговый эффективный ID родительской статьи (структурного узла)
     */
    public function resolveIntermediateNode(Template $childTemplate, ?int $parentId): ?int
    {
        // Если у шаблона нет родительского шаблона и связь не загружена
        if (!$childTemplate->parent_id && !$childTemplate->relationLoaded('parent')) {
            return $parentId;
        }

        $parentArticle = null;
        $parentTemplateId = null;

        if ($parentId !== null) {
            try {
                $parentArticle = Article::find($parentId);
                $parentTemplateId = $parentArticle?->template_id;
            } catch (\Throwable $e) {
                $parentTemplateId = null;
            }
        }

        $targetFound = ($parentId === null);
        try {
            $curr = $childTemplate->relationLoaded('parent')
                ? $childTemplate->getRelation('parent')
                : ($childTemplate->parent_id ? Template::find($childTemplate->parent_id) : null);
        } catch (\Throwable $e) {
            $curr = null;
        }

        $tempChain = [];

        while ($curr) {
            if ($parentTemplateId !== null && $curr->id === $parentTemplateId) {
                $targetFound = true;
                break;
            }

            // Если родитель не указан ($parentId === null) и встретился шаблон статьи (не узел),
            // прерываем, так как статью нельзя создать автоматически
            if ($parentId === null && $curr->type !== 'node') {
                break;
            }

            if ($curr->type === 'node') {
                $tempChain[] = $curr;
            }

            try {
                $curr = $curr->relationLoaded('parent')
                    ? $curr->getRelation('parent')
                    : ($curr->parent_id ? Template::find($curr->parent_id) : null);
            } catch (\Throwable $e) {
                $curr = null;
            }
        }

        // Если в цепочке нет структурных узлов или родитель не найден среди предков шаблона
        if (empty($tempChain) || (!$targetFound && $parentId !== null)) {
            return $parentId;
        }

        // Разворачиваем сверху вниз (от высшего предка к ближайшему)
        $chain = array_reverse($tempChain);
        $effectiveParentId = $parentId;

        foreach ($chain as $nodeTemplate) {
            $nodeSlug = $nodeTemplate->slug ?: Str::slug($nodeTemplate->name);

            try {
                // 1. Поиск по template_id и parent_id
                $query = Article::where('template_id', $nodeTemplate->id);
                if ($effectiveParentId === null) {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $effectiveParentId);
                }
                $existingNode = $query->first();

                // 2. Поиск по slug и parent_id
                if (!$existingNode) {
                    $query = Article::where('slug', $nodeSlug);
                    if ($effectiveParentId === null) {
                        $query->whereNull('parent_id');
                    } else {
                        $query->where('parent_id', $effectiveParentId);
                    }
                    $existingNode = $query->first();
                }

                if ($existingNode) {
                    $effectiveParentId = $existingNode->id;
                } else {
                    $nodeArticle = Article::create([
                        'title'       => $nodeTemplate->name,
                        'slug'        => $nodeSlug,
                        'template_id' => $nodeTemplate->id,
                        'parent_id'   => $effectiveParentId,
                        'type'        => 'node',
                        'status'      => 'published'
                    ]);

                    $effectiveParentId = $nodeArticle->id;
                }
            } catch (\Throwable $e) {
                return $effectiveParentId;
            }
        }

        return $effectiveParentId;
    }
}
