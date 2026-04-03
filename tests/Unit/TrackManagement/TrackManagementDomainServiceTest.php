<?php

declare(strict_types=1);

use App\ProjectManagement\Domain\Dto\ProjectListFilterDto;
use App\ProjectManagement\Domain\Entity\Project;
use App\ProjectManagement\Domain\Entity\ProjectTrackAssignment;
use App\ProjectManagement\Infrastructure\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Infrastructure\Repository\ProjectTrackAssignmentRepositoryInterface;
use App\TrackManagement\Domain\Dto\TrackListFilterDto;
use App\TrackManagement\Domain\Dto\TrackListItemDto;
use App\TrackManagement\Domain\Dto\TrackNamingInputDto;
use App\TrackManagement\Domain\Entity\ChecklistItem;
use App\TrackManagement\Domain\Entity\Track;
use App\TrackManagement\Domain\Enum\TrackStatus;
use App\TrackManagement\Domain\Service\ChecklistDomainServiceInterface;
use App\TrackManagement\Domain\Service\ProgressCalculatorInterface;
use App\TrackManagement\Domain\Service\TrackManagementDomainService;
use App\TrackManagement\Domain\Service\TrackNamingDomainServiceInterface;
use App\TrackManagement\Domain\Service\TrackStatusResolverInterface;
use App\TrackManagement\Infrastructure\Repository\TrackRepositoryInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;

describe('TrackManagementDomainService', function (): void {
    it('derives published flag from at least one active published project', function (): void {
        $service = new TrackManagementDomainService(
            new PublishedTrackInMemoryTrackRepository([
                createPublishedTestTrack('track-1', 1, 'First Beat'),
                createPublishedTestTrack('track-2', 2, 'Second Beat'),
                createPublishedTestTrack('track-3', 3, 'Third Beat'),
            ]),
            new PublishedTrackTrackNamingDomainServiceStub(),
            new PublishedTrackChecklistDomainServiceStub(),
            new PublishedTrackProgressCalculatorStub(),
            new PublishedTrackStatusResolverStub(),
            new PublishedTrackInMemoryProjectTrackAssignmentRepository([
                createPublishedTestProjectAssignment('assignment-1', 'project-1', 'track-2'),
                createPublishedTestProjectAssignment('assignment-2', 'project-2', 'track-3'),
            ]),
            new PublishedTrackInMemoryProjectRepository([
                createPublishedTestProject('project-1', true, true),
                createPublishedTestProject('project-2', false, true),
            ])
        );

        $result = $service->getAllTracks(new TrackListFilterDto('', '', '', 'trackNumber', 'ASC', 1, 50));

        expect(array_map(static fn (TrackListItemDto $item): bool => $item->published, $result->items))
            ->toBe([false, false, true]);
    });
});

function createPublishedTestTrack(string $uuid, int $trackNumber, string $beatName): Track
{
    $track = new Track();
    $track->setUuid($uuid);
    $track->setTrackNumber($trackNumber);
    $track->setBeatName($beatName);
    $track->setTitle($beatName);
    $track->setBpms([120.0]);
    $track->setMusicalKeys(['C Maj']);
    $track->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
    $track->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

    return $track;
}

function createPublishedTestProject(string $uuid, bool $cancelled, bool $published): Project
{
    $project = new Project();
    $project->setUuid($uuid);
    $project->setTitle($uuid);
    $project->setNormalizedTitle($uuid);
    $project->setCategoryUuid('category-1');
    $project->setCancelled($cancelled);
    $project->setPublished($published);
    $project->setPublishedAt($published ? DateAndTimeService::getDateTimeImmutable() : null);
    $project->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
    $project->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

    return $project;
}

function createPublishedTestProjectAssignment(string $uuid, string $projectUuid, string $trackUuid): ProjectTrackAssignment
{
    $assignment = new ProjectTrackAssignment();
    $assignment->setUuid($uuid);
    $assignment->setProjectUuid($projectUuid);
    $assignment->setTrackUuid($trackUuid);
    $assignment->setPosition(1);
    $assignment->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
    $assignment->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

    return $assignment;
}

final readonly class PublishedTrackTrackNamingDomainServiceStub implements TrackNamingDomainServiceInterface
{
    public function buildSuggestedTitle(TrackNamingInputDto $input): string
    {
        return $input->beatName;
    }

    public function buildUpdatedTitleSuggestion(TrackNamingInputDto $input): string
    {
        return $input->beatName;
    }

    public function normalizeBeatName(string $beatName): string
    {
        return $beatName;
    }

    public function normalizeMusicalKeys(array $musicalKeys): string
    {
        return implode(', ', $musicalKeys);
    }

    public function normalizeBpms(array $bpms): string
    {
        return implode(', ', array_map(static fn (float $bpm): string => (string) $bpm, $bpms));
    }
}

final readonly class PublishedTrackChecklistDomainServiceStub implements ChecklistDomainServiceInterface
{
    public function createDefaultChecklistForTrack(string $trackUuid): void
    {
    }

    public function addChecklistItem(App\TrackManagement\Domain\Dto\AddChecklistItemInputDto $input): ChecklistItem
    {
        throw new BadMethodCallException();
    }

    public function renameChecklistItem(App\TrackManagement\Domain\Dto\RenameChecklistItemInputDto $input): void
    {
        throw new BadMethodCallException();
    }

    public function toggleChecklistItem(App\TrackManagement\Domain\Dto\ToggleChecklistItemInputDto $input): void
    {
        throw new BadMethodCallException();
    }

    public function reorderChecklistItems(App\TrackManagement\Domain\Dto\ReorderChecklistItemsInputDto $input): void
    {
        throw new BadMethodCallException();
    }

    public function removeChecklistItem(App\TrackManagement\Domain\Dto\RemoveChecklistItemInputDto $input): void
    {
        throw new BadMethodCallException();
    }

    public function getChecklistItemsByTrackUuid(string $trackUuid): array
    {
        return [];
    }

    public function deleteChecklistByTrackUuid(string $trackUuid): void
    {
    }
}

final readonly class PublishedTrackProgressCalculatorStub implements ProgressCalculatorInterface
{
    public function calculateProgress(array $checklistItems): int
    {
        return 0;
    }
}

final readonly class PublishedTrackStatusResolverStub implements TrackStatusResolverInterface
{
    public function resolveStatus(array $checklistItems): TrackStatus
    {
        return TrackStatus::New;
    }
}

final class PublishedTrackInMemoryTrackRepository implements TrackRepositoryInterface
{
    /**
     * @var array<string, Track>
     */
    private array $tracksByUuid = [];

    /**
     * @param list<Track> $tracks
     */
    public function __construct(array $tracks = [])
    {
        foreach ($tracks as $track) {
            $this->tracksByUuid[$track->getUuid()] = $track;
        }
    }

    public function save(Track $track): void
    {
        $this->tracksByUuid[$track->getUuid()] = $track;
    }

    public function remove(Track $track): void
    {
        unset($this->tracksByUuid[$track->getUuid()]);
    }

    public function getByUuid(string $trackUuid): Track
    {
        return $this->tracksByUuid[$trackUuid] ?? throw new ValueError('Track not found.');
    }

    public function findByUuid(string $trackUuid): ?Track
    {
        return $this->tracksByUuid[$trackUuid] ?? null;
    }

    public function findByTrackNumber(int $trackNumber): ?Track
    {
        foreach ($this->tracksByUuid as $track) {
            if ($track->getTrackNumber() === $trackNumber) {
                return $track;
            }
        }

        return null;
    }

    public function findAllByFilter(TrackListFilterDto $filter): array
    {
        $tracks = array_values($this->tracksByUuid);

        usort(
            $tracks,
            static fn (Track $left, Track $right): int => $left->getTrackNumber() <=> $right->getTrackNumber()
        );

        return $tracks;
    }

    public function getNextTrackNumber(): int
    {
        return count($this->tracksByUuid) + 1;
    }
}

final class PublishedTrackInMemoryProjectTrackAssignmentRepository implements ProjectTrackAssignmentRepositoryInterface
{
    /**
     * @var list<ProjectTrackAssignment>
     */
    private array $assignments;

    /**
     * @param list<ProjectTrackAssignment> $assignments
     */
    public function __construct(array $assignments = [])
    {
        $this->assignments = $assignments;
    }

    public function save(ProjectTrackAssignment $assignment): void
    {
        throw new BadMethodCallException();
    }

    public function saveMany(array $assignments): void
    {
        throw new BadMethodCallException();
    }

    public function remove(ProjectTrackAssignment $assignment): void
    {
        throw new BadMethodCallException();
    }

    public function findByProjectUuidAndTrackUuid(string $projectUuid, string $trackUuid): ?ProjectTrackAssignment
    {
        throw new BadMethodCallException();
    }

    public function findByProjectUuid(string $projectUuid): array
    {
        return array_values(
            array_filter(
                $this->assignments,
                static fn (ProjectTrackAssignment $assignment): bool => $assignment->getProjectUuid() === $projectUuid
            )
        );
    }

    public function findByTrackUuid(string $trackUuid): array
    {
        return array_values(
            array_filter(
                $this->assignments,
                static fn (ProjectTrackAssignment $assignment): bool => $assignment->getTrackUuid() === $trackUuid
            )
        );
    }

    public function getNextPositionForProject(string $projectUuid): int
    {
        throw new BadMethodCallException();
    }

    public function removeAllByProjectUuid(string $projectUuid): void
    {
        throw new BadMethodCallException();
    }

    public function removeAllByTrackUuid(string $trackUuid): void
    {
        throw new BadMethodCallException();
    }
}

final class PublishedTrackInMemoryProjectRepository implements ProjectRepositoryInterface
{
    /**
     * @var array<string, Project>
     */
    private array $projectsByUuid = [];

    /**
     * @param list<Project> $projects
     */
    public function __construct(array $projects = [])
    {
        foreach ($projects as $project) {
            $this->projectsByUuid[$project->getUuid()] = $project;
        }
    }

    public function save(Project $project): void
    {
        $this->projectsByUuid[$project->getUuid()] = $project;
    }

    public function remove(Project $project): void
    {
        unset($this->projectsByUuid[$project->getUuid()]);
    }

    public function getByUuid(string $projectUuid): Project
    {
        return $this->projectsByUuid[$projectUuid] ?? throw new ValueError('Project not found.');
    }

    public function findByUuid(string $projectUuid): ?Project
    {
        return $this->projectsByUuid[$projectUuid] ?? null;
    }

    public function findByNormalizedTitle(string $normalizedTitle): ?Project
    {
        throw new BadMethodCallException();
    }

    public function findAllByFilter(ProjectListFilterDto $filter): array
    {
        return array_values($this->projectsByUuid);
    }
}
