<?php declare(strict_types=1);

namespace Fun\Wiki\Contracts;

interface SyncableEntityInterface
{
    /**
     * Получить хэш содержимого для детекции конфликтов при синхронизации
     */
    public function getContentHash(): string;

    /**
     * Экспорт сущности в сериализуемый массив для API/Local App
     */
    public function toSyncArray(): array;
}
