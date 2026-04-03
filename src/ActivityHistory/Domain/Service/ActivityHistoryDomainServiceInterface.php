<?php

declare(strict_types=1);

namespace App\ActivityHistory\Domain\Service;

use App\ActivityHistory\Domain\Dto\ActivityHistoryListItemDto;
use App\ActivityHistory\Domain\Dto\RecordActivityHistoryEntryInputDto;

interface ActivityHistoryDomainServiceInterface
{
    public function recordEntry(RecordActivityHistoryEntryInputDto $input): void;

    /**
     * @return list<ActivityHistoryListItemDto>
     */
    public function getEntriesByEntity(string $entityType, string $entityUuid, int $limit): array;
}
