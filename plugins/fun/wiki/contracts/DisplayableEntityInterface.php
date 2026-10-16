<?php declare(strict_types=1);

namespace Fun\Wiki\Contracts;

interface DisplayableEntityInterface
{
    /**
     * Получить отфильтрованные данные для отображения в инфобоксе согласно шаблону
     */
    public function getDisplayData(): array;

    /**
     * Получить цепочку навигации (Breadcrumbs)
     */
    public function getBreadcrumbs(): array;
}
