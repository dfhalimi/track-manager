<?php

declare(strict_types=1);

namespace App\ActivityHistory\Facade;

use App\ActivityHistory\Domain\Dto\ActivityHistoryListItemDto;
use App\ActivityHistory\Domain\Service\ActivityHistoryDomainServiceInterface;
use App\ActivityHistory\Facade\Dto\ActivityHistoryEntryDto;

readonly class ActivityHistoryFacade implements ActivityHistoryFacadeInterface
{
    public function __construct(
        private ActivityHistoryDomainServiceInterface $activityHistoryDomainService
    ) {
    }

    public function getEntriesByEntity(string $entityType, string $entityUuid, int $limit): array
    {
        return array_map(
            static fn (ActivityHistoryListItemDto $item): ActivityHistoryEntryDto => new ActivityHistoryEntryDto(
                $item->uuid,
                $item->entityType,
                $item->entityUuid,
                $item->eventType,
                $item->summary,
                $item->details,
                $item->occurredAt
            ),
            $this->activityHistoryDomainService->getEntriesByEntity($entityType, $entityUuid, $limit)
        );
    }
}
