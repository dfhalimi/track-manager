<?php

declare(strict_types=1);

use App\ProjectManagement\Domain\Dto\AddTrackToProjectInputDto;
use App\ProjectManagement\Domain\Dto\CreateProjectInputDto;
use App\ProjectManagement\Domain\Dto\ReorderProjectTracksInputDto;
use App\ProjectManagement\Domain\Entity\Project;
use App\ProjectManagement\Domain\Entity\ProjectCategory;
use App\ProjectManagement\Domain\Entity\ProjectTrackAssignment;
use App\ProjectManagement\Domain\Service\ProjectManagementDomainService;
use App\ProjectManagement\Infrastructure\Repository\ProjectCategoryRepositoryInterface;
use App\ProjectManagement\Infrastructure\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Infrastructure\Repository\ProjectTrackAssignmentRepositoryInterface;
use App\TrackManagement\Facade\Dto\TrackChecklistDto;
use App\TrackManagement\Facade\Dto\TrackDto;
use App\TrackManagement\Facade\Dto\TrackExportDataDto;
use App\TrackManagement\Facade\Dto\TrackNamingDto;
use App\TrackManagement\Facade\Dto\TrackSelectionDto;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;

describe('ProjectManagementDomainService', function (): void {
    it('reuses existing categories instead of creating duplicates', function (): void {
        $categoryRepository = new InMemoryProjectCategoryRepository([
            createProjectCategory('category-1', 'EP', 'ep'),
        ]);

        $service = new ProjectManagementDomainService(
            new InMemoryProjectRepository(),
            $categoryRepository,
            new InMemoryProjectTrackAssignmentRepository(),
            new TrackManagementFacadeStub(['track-1'])
        );

        $project = $service->createProject(new CreateProjectInputDto('Spring Tape', 'ep'));

        expect($project->getCategoryUuid())->toBe('category-1');
        expect($categoryRepository->findAllOrderedByName())->toHaveCount(1);
    });

    it('reorders project tracks and validates duplicates', function (): void {
        $projectRepository = new InMemoryProjectRepository([
            createProject('project-1', 'Spring Tape', 'category-1'),
        ]);

        $assignmentRepository = new InMemoryProjectTrackAssignmentRepository([
            createAssignment('assignment-1', 'project-1', 'track-1', 1),
            createAssignment('assignment-2', 'project-1', 'track-2', 2),
            createAssignment('assignment-3', 'project-1', 'track-3', 3),
        ]);

        $service = new ProjectManagementDomainService(
            $projectRepository,
            new InMemoryProjectCategoryRepository([createProjectCategory('category-1', 'Album', 'album')]),
            $assignmentRepository,
            new TrackManagementFacadeStub(['track-1', 'track-2', 'track-3'])
        );

        $service->reorderProjectTracks(
            new ReorderProjectTracksInputDto('project-1', ['track-3', 'track-1', 'track-2'])
        );

        expect(array_map(static fn (ProjectTrackAssignment $assignment): string => $assignment->getTrackUuid(), $assignmentRepository->findByProjectUuid('project-1')))
            ->toBe(['track-3', 'track-1', 'track-2']);
        expect(array_map(static fn (ProjectTrackAssignment $assignment): int => $assignment->getPosition(), $assignmentRepository->findByProjectUuid('project-1')))
            ->toBe([1, 2, 3]);

        $action = static fn () => $service->reorderProjectTracks(
            new ReorderProjectTracksInputDto('project-1', ['track-1', 'track-1', 'track-2'])
        );

        expect($action)->toThrow(ValueError::class, 'Project track reorder must not contain duplicate track UUIDs.');
    });

    it('prevents adding the same track twice to one project', function (): void {
        $service = new ProjectManagementDomainService(
            new InMemoryProjectRepository([createProject('project-1', 'Spring Tape', 'category-1')]),
            new InMemoryProjectCategoryRepository([createProjectCategory('category-1', 'Single', 'single')]),
            new InMemoryProjectTrackAssignmentRepository([
                createAssignment('assignment-1', 'project-1', 'track-1', 1),
            ]),
            new TrackManagementFacadeStub(['track-1'])
        );

        $action = static fn () => $service->addTrackToProject(
            new AddTrackToProjectInputDto('project-1', 'track-1')
        );

        expect($action)->toThrow(ValueError::class, 'Track is already assigned to this project.');
    });
});

function createProjectCategory(string $uuid, string $name, string $normalizedName): ProjectCategory
{
    $category = new ProjectCategory();
    $category->setUuid($uuid);
    $category->setName($name);
    $category->setNormalizedName($normalizedName);
    $category->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
    $category->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

    return $category;
}

function createProject(string $uuid, string $title, string $categoryUuid): Project
{
    $project = new Project();
    $project->setUuid($uuid);
    $project->setTitle($title);
    $project->setCategoryUuid($categoryUuid);
    $project->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
    $project->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

    return $project;
}

function createAssignment(string $uuid, string $projectUuid, string $trackUuid, int $position): ProjectTrackAssignment
{
    $assignment = new ProjectTrackAssignment();
    $assignment->setUuid($uuid);
    $assignment->setProjectUuid($projectUuid);
    $assignment->setTrackUuid($trackUuid);
    $assignment->setPosition($position);
    $assignment->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
    $assignment->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

    return $assignment;
}

final class InMemoryProjectCategoryRepository implements ProjectCategoryRepositoryInterface
{
    /**
     * @var array<string, ProjectCategory>
     */
    private array $categoriesByUuid = [];

    /**
     * @param list<ProjectCategory> $categories
     */
    public function __construct(array $categories = [])
    {
        foreach ($categories as $category) {
            $this->categoriesByUuid[$category->getUuid()] = $category;
        }
    }

    public function save(ProjectCategory $projectCategory): void
    {
        $this->categoriesByUuid[$projectCategory->getUuid()] = $projectCategory;
    }

    public function getByUuid(string $categoryUuid): ProjectCategory
    {
        $category = $this->findByUuid($categoryUuid);

        if (!$category instanceof ProjectCategory) {
            throw new ValueError('Category not found.');
        }

        return $category;
    }

    public function findByUuid(string $categoryUuid): ?ProjectCategory
    {
        return $this->categoriesByUuid[$categoryUuid] ?? null;
    }

    public function findByNormalizedName(string $normalizedName): ?ProjectCategory
    {
        foreach ($this->categoriesByUuid as $category) {
            if ($category->getNormalizedName() === $normalizedName) {
                return $category;
            }
        }

        return null;
    }

    public function findAllOrderedByName(): array
    {
        $categories = array_values($this->categoriesByUuid);

        usort(
            $categories,
            static fn (ProjectCategory $left, ProjectCategory $right): int => strcmp($left->getName(), $right->getName())
        );

        return $categories;
    }
}

final class InMemoryProjectRepository implements ProjectRepositoryInterface
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
        $project = $this->findByUuid($projectUuid);

        if (!$project instanceof Project) {
            throw new ValueError('Project not found.');
        }

        return $project;
    }

    public function findByUuid(string $projectUuid): ?Project
    {
        return $this->projectsByUuid[$projectUuid] ?? null;
    }

    public function findAllByFilter(App\ProjectManagement\Domain\Dto\ProjectListFilterDto $filter): array
    {
        return array_values($this->projectsByUuid);
    }
}

final class InMemoryProjectTrackAssignmentRepository implements ProjectTrackAssignmentRepositoryInterface
{
    /**
     * @var array<string, ProjectTrackAssignment>
     */
    private array $assignmentsByUuid = [];

    /**
     * @param list<ProjectTrackAssignment> $assignments
     */
    public function __construct(array $assignments = [])
    {
        foreach ($assignments as $assignment) {
            $this->assignmentsByUuid[$assignment->getUuid()] = $assignment;
        }
    }

    public function save(ProjectTrackAssignment $assignment): void
    {
        $this->assignmentsByUuid[$assignment->getUuid()] = $assignment;
    }

    public function saveMany(array $assignments): void
    {
        foreach ($assignments as $assignment) {
            $this->save($assignment);
        }
    }

    public function remove(ProjectTrackAssignment $assignment): void
    {
        unset($this->assignmentsByUuid[$assignment->getUuid()]);
    }

    public function findByProjectUuidAndTrackUuid(string $projectUuid, string $trackUuid): ?ProjectTrackAssignment
    {
        foreach ($this->assignmentsByUuid as $assignment) {
            if ($assignment->getProjectUuid() === $projectUuid && $assignment->getTrackUuid() === $trackUuid) {
                return $assignment;
            }
        }

        return null;
    }

    public function findByProjectUuid(string $projectUuid): array
    {
        $assignments = array_values(
            array_filter(
                $this->assignmentsByUuid,
                static fn (ProjectTrackAssignment $assignment): bool => $assignment->getProjectUuid() === $projectUuid
            )
        );

        usort(
            $assignments,
            static fn (ProjectTrackAssignment $left, ProjectTrackAssignment $right): int => $left->getPosition() <=> $right->getPosition()
        );

        return $assignments;
    }

    public function findByTrackUuid(string $trackUuid): array
    {
        return array_values(
            array_filter(
                $this->assignmentsByUuid,
                static fn (ProjectTrackAssignment $assignment): bool => $assignment->getTrackUuid() === $trackUuid
            )
        );
    }

    public function getNextPositionForProject(string $projectUuid): int
    {
        return count($this->findByProjectUuid($projectUuid)) + 1;
    }

    public function removeAllByProjectUuid(string $projectUuid): void
    {
        foreach ($this->findByProjectUuid($projectUuid) as $assignment) {
            unset($this->assignmentsByUuid[$assignment->getUuid()]);
        }
    }

    public function removeAllByTrackUuid(string $trackUuid): void
    {
        foreach ($this->findByTrackUuid($trackUuid) as $assignment) {
            unset($this->assignmentsByUuid[$assignment->getUuid()]);
        }
    }
}

final readonly class TrackManagementFacadeStub implements TrackManagementFacadeInterface
{
    /**
     * @param list<string> $existingTrackUuids
     */
    public function __construct(
        private array $existingTrackUuids
    ) {
    }

    public function getTrackByUuid(string $trackUuid): TrackDto
    {
        throw new BadMethodCallException();
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
        return in_array($trackUuid, $this->existingTrackUuids, true);
    }

    public function getChecklistByTrackUuid(string $trackUuid): TrackChecklistDto
    {
        throw new BadMethodCallException();
    }

    public function getAllTracksForSelection(): array
    {
        return array_map(
            static fn (string $trackUuid): TrackSelectionDto => new TrackSelectionDto($trackUuid, 1, 'Beat', 'Title', null),
            $this->existingTrackUuids
        );
    }
}
