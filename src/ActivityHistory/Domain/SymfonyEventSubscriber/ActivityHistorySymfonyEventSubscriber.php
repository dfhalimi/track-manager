<?php

declare(strict_types=1);

namespace App\ActivityHistory\Domain\SymfonyEventSubscriber;

use App\ActivityHistory\Domain\Dto\RecordActivityHistoryEntryInputDto;
use App\ActivityHistory\Domain\Service\ActivityHistoryDomainServiceInterface;
use App\FileImport\Facade\SymfonyEvent\TrackFileReplacedSymfonyEvent;
use App\FileImport\Facade\SymfonyEvent\TrackFileUploadedSymfonyEvent;
use App\MediaAssetManagement\Facade\SymfonyEvent\ProjectImageReplacedSymfonyEvent;
use App\MediaAssetManagement\Facade\SymfonyEvent\ProjectImageUploadedSymfonyEvent;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectCancelledSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectCreatedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectPublishedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectReactivatedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectTracksReorderedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectUnpublishedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectUpdatedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\TrackAssignedToProjectSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\TrackRemovedFromProjectSymfonyEvent;
use App\TrackManagement\Facade\SymfonyEvent\TrackCancelledSymfonyEvent;
use App\TrackManagement\Facade\SymfonyEvent\TrackCreatedSymfonyEvent;
use App\TrackManagement\Facade\SymfonyEvent\TrackReactivatedSymfonyEvent;
use App\TrackManagement\Facade\SymfonyEvent\TrackStatusChangedSymfonyEvent;
use App\TrackManagement\Facade\SymfonyEvent\TrackUpdatedSymfonyEvent;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TrackCreatedSymfonyEvent::class, method: 'onTrackCreated')]
#[AsEventListener(event: TrackUpdatedSymfonyEvent::class, method: 'onTrackUpdated')]
#[AsEventListener(event: TrackCancelledSymfonyEvent::class, method: 'onTrackCancelled')]
#[AsEventListener(event: TrackReactivatedSymfonyEvent::class, method: 'onTrackReactivated')]
#[AsEventListener(event: TrackStatusChangedSymfonyEvent::class, method: 'onTrackStatusChanged')]
#[AsEventListener(event: ProjectCreatedSymfonyEvent::class, method: 'onProjectCreated')]
#[AsEventListener(event: ProjectUpdatedSymfonyEvent::class, method: 'onProjectUpdated')]
#[AsEventListener(event: ProjectCancelledSymfonyEvent::class, method: 'onProjectCancelled')]
#[AsEventListener(event: ProjectReactivatedSymfonyEvent::class, method: 'onProjectReactivated')]
#[AsEventListener(event: ProjectPublishedSymfonyEvent::class, method: 'onProjectPublished')]
#[AsEventListener(event: ProjectUnpublishedSymfonyEvent::class, method: 'onProjectUnpublished')]
#[AsEventListener(event: TrackAssignedToProjectSymfonyEvent::class, method: 'onTrackAssignedToProject')]
#[AsEventListener(event: TrackRemovedFromProjectSymfonyEvent::class, method: 'onTrackRemovedFromProject')]
#[AsEventListener(event: ProjectTracksReorderedSymfonyEvent::class, method: 'onProjectTracksReordered')]
#[AsEventListener(event: TrackFileUploadedSymfonyEvent::class, method: 'onTrackFileUploaded')]
#[AsEventListener(event: TrackFileReplacedSymfonyEvent::class, method: 'onTrackFileReplaced')]
#[AsEventListener(event: ProjectImageUploadedSymfonyEvent::class, method: 'onProjectImageUploaded')]
#[AsEventListener(event: ProjectImageReplacedSymfonyEvent::class, method: 'onProjectImageReplaced')]
readonly class ActivityHistorySymfonyEventSubscriber
{
    public function __construct(
        private ActivityHistoryDomainServiceInterface $activityHistoryDomainService,
        private TrackManagementFacadeInterface        $trackManagementFacade,
        private ProjectManagementFacadeInterface      $projectManagementFacade
    ) {
    }

    public function onTrackCreated(TrackCreatedSymfonyEvent $event): void
    {
        $this->record('track', $event->trackUuid, 'created', 'Track erstellt', $event->details, $event->occurredAt);
    }

    public function onTrackUpdated(TrackUpdatedSymfonyEvent $event): void
    {
        $this->record('track', $event->trackUuid, 'updated', 'Track bearbeitet', $event->details, $event->occurredAt);
    }

    public function onTrackCancelled(TrackCancelledSymfonyEvent $event): void
    {
        $this->record('track', $event->trackUuid, 'cancelled', 'Track archiviert', [], $event->occurredAt);
    }

    public function onTrackReactivated(TrackReactivatedSymfonyEvent $event): void
    {
        $this->record('track', $event->trackUuid, 'reactivated', 'Track reaktiviert', [], $event->occurredAt);
    }

    public function onTrackStatusChanged(TrackStatusChangedSymfonyEvent $event): void
    {
        $this->record(
            'track',
            $event->trackUuid,
            'status_changed',
            'Track-Status geändert',
            [sprintf('Status: %s -> %s', $event->fromStatus->getLabel(), $event->toStatus->getLabel())],
            $event->occurredAt
        );
    }

    public function onProjectCreated(ProjectCreatedSymfonyEvent $event): void
    {
        $this->record('project', $event->projectUuid, 'created', 'Projekt erstellt', $event->details, $event->occurredAt);
    }

    public function onProjectUpdated(ProjectUpdatedSymfonyEvent $event): void
    {
        $this->record('project', $event->projectUuid, 'updated', 'Projekt bearbeitet', $event->details, $event->occurredAt);
    }

    public function onProjectCancelled(ProjectCancelledSymfonyEvent $event): void
    {
        $this->record('project', $event->projectUuid, 'cancelled', 'Projekt archiviert', [], $event->occurredAt);
    }

    public function onProjectReactivated(ProjectReactivatedSymfonyEvent $event): void
    {
        $this->record('project', $event->projectUuid, 'reactivated', 'Projekt reaktiviert', [], $event->occurredAt);
    }

    public function onProjectPublished(ProjectPublishedSymfonyEvent $event): void
    {
        $this->record('project', $event->projectUuid, 'published', 'Projekt veröffentlicht', [], $event->occurredAt);
    }

    public function onProjectUnpublished(ProjectUnpublishedSymfonyEvent $event): void
    {
        $this->record('project', $event->projectUuid, 'unpublished', 'Projekt auf unveröffentlicht gesetzt', [], $event->occurredAt);
    }

    public function onTrackAssignedToProject(TrackAssignedToProjectSymfonyEvent $event): void
    {
        $project = $this->projectManagementFacade->getProjectByUuid($event->projectUuid);
        $track   = $this->trackManagementFacade->getTrackByUuid($event->trackUuid);

        $this->record(
            'project',
            $event->projectUuid,
            'track_assigned',
            'Track hinzugefügt',
            [$this->formatTrackLabel($track) . sprintf(' wurde auf Position %d hinzugefügt.', $event->position)],
            $event->occurredAt
        );

        $this->record(
            'track',
            $event->trackUuid,
            'project_assigned',
            'Projektzuweisung hinzugefügt',
            [sprintf('Projekt "%s" wurde zugewiesen.', $project->title)],
            $event->occurredAt
        );
    }

    public function onTrackRemovedFromProject(TrackRemovedFromProjectSymfonyEvent $event): void
    {
        $project = $this->projectManagementFacade->getProjectByUuid($event->projectUuid);
        $track   = $this->trackManagementFacade->getTrackByUuid($event->trackUuid);

        $this->record(
            'project',
            $event->projectUuid,
            'track_removed',
            'Track entfernt',
            [$this->formatTrackLabel($track) . ' wurde aus dem Projekt entfernt.'],
            $event->occurredAt
        );

        $this->record(
            'track',
            $event->trackUuid,
            'project_removed',
            'Projektzuweisung entfernt',
            [sprintf('Projekt "%s" wurde entfernt.', $project->title)],
            $event->occurredAt
        );
    }

    public function onProjectTracksReordered(ProjectTracksReorderedSymfonyEvent $event): void
    {
        $details = [];
        foreach ($event->orderedTrackUuids as $index => $trackUuid) {
            $details[] = sprintf('%d. %s', $index + 1, $this->formatTrackLabel($this->trackManagementFacade->getTrackByUuid($trackUuid)));
        }

        $this->record(
            'project',
            $event->projectUuid,
            'tracks_reordered',
            'Track-Reihenfolge geändert',
            $details,
            $event->occurredAt
        );
    }

    public function onTrackFileUploaded(TrackFileUploadedSymfonyEvent $event): void
    {
        $this->record(
            'track',
            $event->trackUuid,
            'track_file_uploaded',
            'Datei hochgeladen',
            [sprintf('Datei "%s".', $event->originalFilename)],
            $event->occurredAt
        );
    }

    public function onTrackFileReplaced(TrackFileReplacedSymfonyEvent $event): void
    {
        $this->record(
            'track',
            $event->trackUuid,
            'track_file_replaced',
            'Datei ersetzt',
            [sprintf('Datei "%s".', $event->originalFilename)],
            $event->occurredAt
        );
    }

    public function onProjectImageUploaded(ProjectImageUploadedSymfonyEvent $event): void
    {
        $this->record(
            'project',
            $event->projectUuid,
            'project_image_uploaded',
            'Projektbild hochgeladen',
            [sprintf('Bild "%s".', $event->originalFilename)],
            $event->occurredAt
        );
    }

    public function onProjectImageReplaced(ProjectImageReplacedSymfonyEvent $event): void
    {
        $this->record(
            'project',
            $event->projectUuid,
            'project_image_replaced',
            'Projektbild ersetzt',
            [sprintf('Bild "%s".', $event->originalFilename)],
            $event->occurredAt
        );
    }

    /**
     * @param list<string> $details
     */
    private function record(
        string            $entityType,
        string            $entityUuid,
        string            $eventType,
        string            $summary,
        array             $details,
        DateTimeImmutable $occurredAt
    ): void {
        $this->activityHistoryDomainService->recordEntry(
            new RecordActivityHistoryEntryInputDto($entityType, $entityUuid, $eventType, $summary, $details, $occurredAt)
        );
    }

    private function formatTrackLabel(\App\TrackManagement\Facade\Dto\TrackDto $track): string
    {
        return sprintf('Track %d "%s"', $track->trackNumber, $track->title);
    }
}
