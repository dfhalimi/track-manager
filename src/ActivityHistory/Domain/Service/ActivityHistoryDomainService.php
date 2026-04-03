<?php

declare(strict_types=1);

namespace App\ActivityHistory\Domain\Service;

use App\ActivityHistory\Domain\Dto\ActivityHistoryListItemDto;
use App\ActivityHistory\Domain\Dto\RecordActivityHistoryEntryInputDto;
use App\ActivityHistory\Domain\Entity\ActivityHistoryEntry;
use App\ActivityHistory\Infrastructure\Repository\ActivityHistoryEntryRepositoryInterface;
use Symfony\Component\Uid\Uuid;

readonly class ActivityHistoryDomainService implements ActivityHistoryDomainServiceInterface
{
    public function __construct(
        private ActivityHistoryEntryRepositoryInterface $activityHistoryEntryRepository
    ) {
    }

    public function recordEntry(RecordActivityHistoryEntryInputDto $input): void
    {
        $entry = new ActivityHistoryEntry();
        $entry->setUuid(Uuid::v7()->toRfc4122());
        $entry->setEntityType($input->entityType);
        $entry->setEntityUuid($input->entityUuid);
        $entry->setEventType($input->eventType);
        $entry->setSummary($input->summary);
        $entry->setDetails($input->details);
        $entry->setOccurredAt($input->occurredAt);

        $this->activityHistoryEntryRepository->save($entry);
    }

    public function getEntriesByEntity(string $entityType, string $entityUuid, int $limit): array
    {
        $items = [];

        foreach ($this->activityHistoryEntryRepository->findByEntity($entityType, $entityUuid, $limit) as $entry) {
            $items[] = new ActivityHistoryListItemDto(
                $entry->getUuid(),
                $entry->getEntityType(),
                $entry->getEntityUuid(),
                $entry->getEventType(),
                $entry->getSummary(),
                $entry->getDetails(),
                $entry->getOccurredAt()
            );
        }

        return $items;
    }
}
