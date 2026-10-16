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
     * Разрешение и дедупликация обязательного промежуточного структурного узла (Node)
     */
    public function resolveIntermediateNode(Template $childTemplate, ?int $parentId): ?int
    {
        if (!$childTemplate->parent_id && !$childTemplate->relationLoaded('parent')) {
            return $parentId;
        }

        $parentTemplate = $childTemplate->parent;
        if (!$parentTemplate) {
            return $parentId;
        }

        // Если родительский шаблон является обязательным Узлом (Node)
        if ($parentTemplate->type === 'node' && $parentTemplate->is_mandatory) {
            $nodeSlug = $parentTemplate->slug ?: Str::slug($parentTemplate->name);

            // Проверяем наличие узла под данным предком (дедушкой)
            $existingNode = Article::where('template_id', $parentTemplate->id)
                ->where('slug', $nodeSlug)
                ->where('parent_id', $parentId)
                ->first();

            if ($existingNode) {
                return $existingNode->id;
            }

            // Автоматически создаем структурный узел
            $nodeArticle = Article::create([
                'title'       => $parentTemplate->name,
                'slug'        => $nodeSlug,
                'template_id' => $parentTemplate->id,
                'parent_id'   => $parentId,
                'type'        => 'node',
                'status'      => 'published'
            ]);

            return $nodeArticle->id;
        }

        return $parentId;
    }
}
