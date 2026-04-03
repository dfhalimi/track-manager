<?php

declare(strict_types=1);

namespace App\ActivityHistory\Facade;

use App\ActivityHistory\Facade\Dto\ActivityHistoryEntryDto;

interface ActivityHistoryFacadeInterface
{
    /**
     * @return list<ActivityHistoryEntryDto>
     */
    public function getEntriesByEntity(string $entityType, string $entityUuid, int $limit): array;
}
