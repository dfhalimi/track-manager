<?php

declare(strict_types=1);

use App\ActivityHistory\Domain\Dto\RecordActivityHistoryEntryInputDto;
use App\ActivityHistory\Domain\Service\ActivityHistoryDomainServiceInterface;
use App\ActivityHistory\Domain\SymfonyEventSubscriber\ActivityHistorySymfonyEventSubscriber;
use App\Common\Service\LocalizedDateTimeService;
use App\ProjectManagement\Facade\Dto\ProjectDto;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectPublishedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\TrackAssignedToProjectSymfonyEvent;
use App\TrackManagement\Domain\Enum\TrackStatus;
use App\TrackManagement\Facade\Dto\TrackChecklistDto;
use App\TrackManagement\Facade\Dto\TrackDto;
use App\TrackManagement\Facade\Dto\TrackExportDataDto;
use App\TrackManagement\Facade\Dto\TrackNamingDto;
use App\TrackManagement\Facade\SymfonyEvent\TrackStatusChangedSymfonyEvent;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;

describe('ActivityHistorySymfonyEventSubscriber', function (): void {
    it('writes project and track history for a project assignment', function (): void {
        $historyDomainService = new RecordingActivityHistoryDomainService();
        $subscriber           = new ActivityHistorySymfonyEventSubscriber(
            $historyDomainService,
            new ActivityHistoryTrackManagementFacadeStub(),
            new ActivityHistoryProjectManagementFacadeStub(),
            new LocalizedDateTimeService('Europe/Berlin')
        );

        $subscriber->onTrackAssignedToProject(
            new TrackAssignedToProjectSymfonyEvent(
                'project-1',
                'track-1',
                2,
                DateAndTimeService::getDateTimeImmutable()
            )
        );

        expect($historyDomainService->recordedInputs)->toHaveCount(2);

        expect($historyDomainService->recordedInputs[0]->entityType)->toBe('project');
        expect($historyDomainService->recordedInputs[0]->summary)->toBe('Track hinzugefügt');
        expect($historyDomainService->recordedInputs[0]->details)->toBe(['Track 7 "Alpha Title" wurde auf Position 2 hinzugefügt.']);

        expect($historyDomainService->recordedInputs[1]->entityType)->toBe('track');
        expect($historyDomainService->recordedInputs[1]->summary)->toBe('Projektzuweisung hinzugefügt');
        expect($historyDomainService->recordedInputs[1]->details)->toBe(['Projekt "Project One" wurde zugewiesen.']);
    });

    it('writes track history for a track status change', function (): void {
        $historyDomainService = new RecordingActivityHistoryDomainService();
        $subscriber           = new ActivityHistorySymfonyEventSubscriber(
            $historyDomainService,
            new ActivityHistoryTrackManagementFacadeStub(),
            new ActivityHistoryProjectManagementFacadeStub(),
            new LocalizedDateTimeService('Europe/Berlin')
        );

        $subscriber->onTrackStatusChanged(
            new TrackStatusChangedSymfonyEvent(
                'track-1',
                TrackStatus::New,
                TrackStatus::InProgress,
                DateAndTimeService::getDateTimeImmutable()
            )
        );

        expect($historyDomainService->recordedInputs)->toHaveCount(1);
        expect($historyDomainService->recordedInputs[0]->entityType)->toBe('track');
        expect($historyDomainService->recordedInputs[0]->summary)->toBe('Track-Status geändert');
        expect($historyDomainService->recordedInputs[0]->details)->toBe(['Status: New -> In Progress']);
    });

    it('writes the actual release date when a project is published', function (): void {
        $historyDomainService = new RecordingActivityHistoryDomainService();
        $subscriber           = new ActivityHistorySymfonyEventSubscriber(
            $historyDomainService,
            new ActivityHistoryTrackManagementFacadeStub(),
            new ActivityHistoryProjectManagementFacadeStub(),
            new LocalizedDateTimeService('Europe/Berlin')
        );
        $publishedAt = createActivityHistoryTestDateTime('2026-04-01 10:15');

        $subscriber->onProjectPublished(
            new ProjectPublishedSymfonyEvent(
                'project-1',
                $publishedAt,
                DateAndTimeService::getDateTimeImmutable()
            )
        );

        expect($historyDomainService->recordedInputs)->toHaveCount(1);
        expect($historyDomainService->recordedInputs[0]->entityType)->toBe('project');
        expect($historyDomainService->recordedInputs[0]->summary)->toBe('Projekt veröffentlicht');
        expect($historyDomainService->recordedInputs[0]->details)->toBe(['Veröffentlichungsdatum: 01.04.2026 12:15']);
    });
});

final class RecordingActivityHistoryDomainService implements ActivityHistoryDomainServiceInterface
{
    /**
     * @var list<RecordActivityHistoryEntryInputDto>
     */
    public array $recordedInputs = [];

    public function recordEntry(RecordActivityHistoryEntryInputDto $input): void
    {
        $this->recordedInputs[] = $input;
    }

    public function getEntriesByEntity(string $entityType, string $entityUuid, int $limit): array
    {
        return [];
    }
}

function createActivityHistoryTestDateTime(string $dateTime): DateTimeImmutable
{
    [$datePart, $timePart] = explode(' ', $dateTime);
    [$year, $month, $day]  = array_map('intval', explode('-', $datePart));
    [$hour, $minute]       = array_map('intval', explode(':', $timePart));

    return DateAndTimeService::getDateTimeImmutable()
        ->setTimezone(new DateTimeZone('UTC'))
        ->setDate($year, $month, $day)
        ->setTime($hour, $minute);
}

final readonly class ActivityHistoryTrackManagementFacadeStub implements TrackManagementFacadeInterface
{
    public function getTrackByUuid(string $trackUuid): TrackDto
    {
        return new TrackDto(
            $trackUuid,
            7,
            'Beat',
            'Alpha Title',
            null,
            [120.0],
            ['C Maj'],
            null,
            null,
            false,
            DateAndTimeService::getDateTimeImmutable(),
            DateAndTimeService::getDateTimeImmutable()
        );
    }

    public function getTrackByTrackNumber(int $trackNumber): ?TrackDto
    {
        throw new BadMethodCallException();
    }

    public function getTrackExportData(string $trackUuid): TrackExportDataDto
    {
        throw new BadMethodCallException();
    }

    public function getTrackNamingData(string $trackUuid): TrackNamingDto
    {
        throw new BadMethodCallException();
    }

    public function trackExists(string $trackUuid): bool
    {
        throw new BadMethodCallException();
    }

    public function getChecklistByTrackUuid(string $trackUuid): TrackChecklistDto
    {
        throw new BadMethodCallException();
    }

    public function getAllTracksForSelection(): array
    {
        throw new BadMethodCallException();
    }
}

final readonly class ActivityHistoryProjectManagementFacadeStub implements ProjectManagementFacadeInterface
{
    public function getProjectByUuid(string $projectUuid): ProjectDto
    {
        return new ProjectDto(
            $projectUuid,
            'Project One',
            'category-1',
            'Single',
            false,
            false,
            null,
            DateAndTimeService::getDateTimeImmutable(),
            DateAndTimeService::getDateTimeImmutable()
        );
    }

    public function projectExists(string $projectUuid): bool
    {
        throw new BadMethodCallException();
    }

    public function getAllProjectCategories(): array
    {
        throw new BadMethodCallException();
    }

    public function getTrackAssignmentsByProjectUuid(string $projectUuid): array
    {
        throw new BadMethodCallException();
    }

    public function getProjectsByTrackUuid(string $trackUuid): array
    {
        throw new BadMethodCallException();
    }

    public function removeTrackFromAllProjects(string $trackUuid): void
    {
        throw new BadMethodCallException();
    }

    public function removeTrackFromActiveProjects(string $trackUuid): void
    {
        throw new BadMethodCallException();
    }
}
