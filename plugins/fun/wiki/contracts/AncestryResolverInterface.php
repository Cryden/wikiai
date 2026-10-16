<?php declare(strict_types=1);

namespace Fun\Wiki\Contracts;

use Fun\Wiki\Models\Article;
use Fun\Wiki\Models\Template;

interface AncestryResolverInterface
{
    /**
     * Создание или привязка статьи с автоматическим разрешением цепи предков
     * и дедупликацией промежуточных обязательных структурных узлов (Nodes).
     */
    public function resolveAndCreate(Template $targetTemplate, array $payload, ?int $explicitParentId = null): Article;

    /**
     * Проверка и автосоздание промежуточного узла по сметке слага
     */
    public function resolveIntermediateNode(Template $childTemplate, ?int $parentId): ?int;
}
