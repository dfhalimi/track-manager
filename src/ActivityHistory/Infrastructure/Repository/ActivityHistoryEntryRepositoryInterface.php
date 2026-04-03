<?php

declare(strict_types=1);

namespace App\ActivityHistory\Infrastructure\Repository;

use App\ActivityHistory\Domain\Entity\ActivityHistoryEntry;

interface ActivityHistoryEntryRepositoryInterface
{
    public function save(ActivityHistoryEntry $entry): void;

    /**
     * @return list<ActivityHistoryEntry>
     */
    public function findByEntity(string $entityType, string $entityUuid, int $limit): array;
}
