<?php

declare(strict_types=1);

namespace App\ActivityHistory\Presentation\Service;

use App\ActivityHistory\Domain\Dto\ActivityHistoryListItemDto;
use App\ActivityHistory\Domain\Service\ActivityHistoryDomainServiceInterface;
use App\ActivityHistory\Presentation\Dto\ActivityHistoryEntryViewDto;
use App\ActivityHistory\Presentation\Dto\ActivityHistoryModalViewDto;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use DateTimeImmutable;

readonly class ActivityHistoryPresentationService implements ActivityHistoryPresentationServiceInterface
{
    public function __construct(
        private ActivityHistoryDomainServiceInterface $activityHistoryDomainService,
        private TrackManagementFacadeInterface        $trackManagementFacade,
        private ProjectManagementFacadeInterface      $projectManagementFacade
    ) {
    }

    public function buildTrackHistoryModalViewDto(string $trackUuid, int $limit = 50): ActivityHistoryModalViewDto
    {
        $track = $this->trackManagementFacade->getTrackByUuid($trackUuid);

        return new ActivityHistoryModalViewDto(
            sprintf('Track-Historie: Track %d', $track->trackNumber),
            sprintf('Historie für "%s"', $track->title),
            $this->buildEntries('track', $trackUuid, $track->createdAt, $limit)
        );
    }

    public function buildProjectHistoryModalViewDto(string $projectUuid, int $limit = 50): ActivityHistoryModalViewDto
    {
        $project = $this->projectManagementFacade->getProjectByUuid($projectUuid);

        return new ActivityHistoryModalViewDto(
            sprintf('Projekt-Historie: %s', $project->title),
            'Chronologische Historie aller Änderungen und Zuordnungen.',
            $this->buildEntries('project', $projectUuid, $project->createdAt, $limit)
        );
    }

    /**
     * @return list<ActivityHistoryEntryViewDto>
     */
    private function buildEntries(string $entityType, string $entityUuid, DateTimeImmutable $createdAt, int $limit): array
    {
        $entries        = [];
        $historyEntries = $this->activityHistoryDomainService->getEntriesByEntity($entityType, $entityUuid, $limit);

        foreach ($historyEntries as $entry) {
            $entries[] = new ActivityHistoryEntryViewDto(
                $entry->summary,
                $entry->details,
                $entry->occurredAt->format('d.m.Y H:i'),
                false
            );
        }

        if (!$this->containsCreatedEntry($historyEntries)) {
            $entries[] = new ActivityHistoryEntryViewDto(
                'Erstellt',
                [],
                $createdAt->format('d.m.Y H:i'),
                true
            );
        }

        return $entries;
    }

    /**
     * @param list<ActivityHistoryListItemDto> $entries
     */
    private function containsCreatedEntry(array $entries): bool
    {
        foreach ($entries as $entry) {
            if ($entry->eventType === 'created') {
                return true;
            }
        }

        return false;
    }
}
