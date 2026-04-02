<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Service;

use App\ProjectManagement\Domain\Dto\AddTrackToProjectInputDto;
use App\ProjectManagement\Domain\Dto\CreateProjectInputDto;
use App\ProjectManagement\Domain\Dto\ProjectListFilterDto;
use App\ProjectManagement\Domain\Dto\ProjectListItemDto;
use App\ProjectManagement\Domain\Dto\ProjectListResultDto;
use App\ProjectManagement\Domain\Dto\RemoveTrackFromProjectInputDto;
use App\ProjectManagement\Domain\Dto\ReorderProjectTracksInputDto;
use App\ProjectManagement\Domain\Dto\UpdateProjectInputDto;
use App\ProjectManagement\Domain\Entity\Project;
use App\ProjectManagement\Domain\Entity\ProjectCategory;
use App\ProjectManagement\Domain\Entity\ProjectTrackAssignment;
use App\ProjectManagement\Domain\Support\ProjectCategoryCatalog;
use App\ProjectManagement\Infrastructure\Repository\ProjectCategoryRepositoryInterface;
use App\ProjectManagement\Infrastructure\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Infrastructure\Repository\ProjectTrackAssignmentRepositoryInterface;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Component\Uid\Uuid;
use ValueError;

readonly class ProjectManagementDomainService implements ProjectManagementDomainServiceInterface
{
    public function __construct(
        private ProjectRepositoryInterface                $projectRepository,
        private ProjectCategoryRepositoryInterface        $projectCategoryRepository,
        private ProjectTrackAssignmentRepositoryInterface $projectTrackAssignmentRepository,
        private TrackManagementFacadeInterface            $trackManagementFacade
    ) {
    }

    public function createProject(CreateProjectInputDto $input): Project
    {
        $now = DateAndTimeService::getDateTimeImmutable();

        $project = new Project();
        $project->setUuid(Uuid::v7()->toRfc4122());
        $project->setTitle($this->normalizeTitle($input->title));
        $project->setCategoryUuid($this->resolveCategory($input->categoryName)->getUuid());
        $project->setCreatedAt($now);
        $project->setUpdatedAt($now);

        $this->validateProject($project);
        $this->projectRepository->save($project);

        return $project;
    }

    public function updateProject(UpdateProjectInputDto $input): Project
    {
        $project = $this->projectRepository->getByUuid($input->projectUuid);

        $project->setTitle($this->normalizeTitle($input->title));
        $project->setCategoryUuid($this->resolveCategory($input->categoryName)->getUuid());
        $project->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

        $this->validateProject($project);
        $this->projectRepository->save($project);

        return $project;
    }

    public function deleteProject(string $projectUuid): void
    {
        $project = $this->projectRepository->getByUuid($projectUuid);
        $this->projectTrackAssignmentRepository->removeAllByProjectUuid($projectUuid);
        $this->projectRepository->remove($project);
    }

    public function getProjectByUuid(string $projectUuid): Project
    {
        return $this->projectRepository->getByUuid($projectUuid);
    }

    public function getAllProjectCategories(): array
    {
        return $this->projectCategoryRepository->findAllOrderedByName();
    }

    public function getProjectCategoryByUuid(string $categoryUuid): ProjectCategory
    {
        return $this->projectCategoryRepository->getByUuid($categoryUuid);
    }

    public function getAllProjects(ProjectListFilterDto $filter): ProjectListResultDto
    {
        $items       = $this->buildFilteredProjectListItems($filter);
        $totalItems  = count($items);
        $perPage     = $this->normalizePerPage($filter->perPage);
        $totalPages  = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min(max(1, $filter->page), $totalPages);
        $offset      = ($currentPage - 1) * $perPage;

        return new ProjectListResultDto(
            array_slice($items, $offset, $perPage),
            $totalItems,
            $currentPage,
            $perPage,
            $totalPages
        );
    }

    public function getProjectSearchSuggestions(ProjectListFilterDto $filter, int $limit): array
    {
        $searchQuery = trim((string) ($filter->searchQuery ?? ''));
        if ($searchQuery === '') {
            return [];
        }

        $suggestions = [];
        foreach ($this->buildFilteredProjectListItems($filter) as $item) {
            foreach ([$item->title, $item->categoryName] as $candidate) {
                if (mb_stripos($candidate, $searchQuery) === false) {
                    continue;
                }

                $normalizedCandidate = mb_strtolower($candidate);
                if (array_key_exists($normalizedCandidate, $suggestions)) {
                    continue;
                }

                $suggestions[$normalizedCandidate] = $candidate;
                if (count($suggestions) >= $limit) {
                    break 2;
                }
            }
        }

        return array_values($suggestions);
    }

    /**
     * @return list<ProjectListItemDto>
     */
    private function buildFilteredProjectListItems(ProjectListFilterDto $filter): array
    {
        $items = [];

        foreach ($this->projectRepository->findAllByFilter($filter) as $project) {
            $items[] = new ProjectListItemDto(
                $project->getUuid(),
                $project->getTitle(),
                $this->projectCategoryRepository->getByUuid($project->getCategoryUuid())->getName(),
                count($this->projectTrackAssignmentRepository->findByProjectUuid($project->getUuid())),
                $project->getUpdatedAt()
            );
        }

        if ($filter->sortBy === 'trackCount') {
            usort(
                $items,
                fn (ProjectListItemDto $left, ProjectListItemDto $right): int => strtoupper((string) ($filter->sortDirection ?? 'DESC')) === 'ASC'
                    ? $left->trackCount  <=> $right->trackCount
                    : $right->trackCount <=> $left->trackCount
            );
        }

        return $items;
    }

    public function getTrackAssignmentsByProjectUuid(string $projectUuid): array
    {
        $this->projectRepository->getByUuid($projectUuid);

        return $this->projectTrackAssignmentRepository->findByProjectUuid($projectUuid);
    }

    public function getTrackAssignmentsByTrackUuid(string $trackUuid): array
    {
        return $this->projectTrackAssignmentRepository->findByTrackUuid($trackUuid);
    }

    public function addTrackToProject(AddTrackToProjectInputDto $input): ProjectTrackAssignment
    {
        $project = $this->projectRepository->getByUuid($input->projectUuid);
        $this->ensureTrackExists($input->trackUuid);

        if ($this->projectTrackAssignmentRepository->findByProjectUuidAndTrackUuid($input->projectUuid, $input->trackUuid) !== null) {
            throw new ValueError('Track is already assigned to this project.');
        }

        $now = DateAndTimeService::getDateTimeImmutable();

        $assignment = new ProjectTrackAssignment();
        $assignment->setUuid(Uuid::v7()->toRfc4122());
        $assignment->setProjectUuid($input->projectUuid);
        $assignment->setTrackUuid($input->trackUuid);
        $assignment->setPosition($this->projectTrackAssignmentRepository->getNextPositionForProject($input->projectUuid));
        $assignment->setCreatedAt($now);
        $assignment->setUpdatedAt($now);

        $this->projectTrackAssignmentRepository->save($assignment);
        $project->setUpdatedAt($now);
        $this->projectRepository->save($project);

        return $assignment;
    }

    public function removeTrackFromProject(RemoveTrackFromProjectInputDto $input): void
    {
        $project    = $this->projectRepository->getByUuid($input->projectUuid);
        $assignment = $this->projectTrackAssignmentRepository->findByProjectUuidAndTrackUuid($input->projectUuid, $input->trackUuid);

        if ($assignment === null) {
            return;
        }

        $this->projectTrackAssignmentRepository->remove($assignment);
        $this->resequenceAssignments($input->projectUuid);
        $project->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());
        $this->projectRepository->save($project);
    }

    public function reorderProjectTracks(ReorderProjectTracksInputDto $input): void
    {
        $project           = $this->projectRepository->getByUuid($input->projectUuid);
        $assignments       = $this->projectTrackAssignmentRepository->findByProjectUuid($input->projectUuid);
        $orderedTrackUuids = $input->orderedTrackUuids;

        if (count(array_unique($orderedTrackUuids)) !== count($orderedTrackUuids)) {
            throw new ValueError('Project track reorder must not contain duplicate track UUIDs.');
        }

        if (count($assignments) !== count($orderedTrackUuids)) {
            throw new ValueError('Project track reorder must include all tracks exactly once.');
        }

        $assignmentsByTrackUuid = [];
        foreach ($assignments as $assignment) {
            $assignmentsByTrackUuid[$assignment->getTrackUuid()] = $assignment;
        }

        $reorderedAssignments = [];
        foreach ($orderedTrackUuids as $index => $trackUuid) {
            $assignment = $assignmentsByTrackUuid[$trackUuid] ?? null;

            if (!$assignment instanceof ProjectTrackAssignment) {
                throw new ValueError('Project track reorder contains an unknown track UUID.');
            }

            $assignment->setPosition($index + 1);
            $assignment->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());
            $reorderedAssignments[] = $assignment;
        }

        $this->projectTrackAssignmentRepository->saveMany($reorderedAssignments);
        $project->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());
        $this->projectRepository->save($project);
    }

    public function removeTrackFromAllProjects(string $trackUuid): void
    {
        $assignments          = $this->projectTrackAssignmentRepository->findByTrackUuid($trackUuid);
        $affectedProjectUuids = array_values(
            array_unique(
                array_map(
                    static fn (ProjectTrackAssignment $assignment): string => $assignment->getProjectUuid(),
                    $assignments
                )
            )
        );

        $this->projectTrackAssignmentRepository->removeAllByTrackUuid($trackUuid);

        foreach ($affectedProjectUuids as $projectUuid) {
            $this->resequenceAssignments($projectUuid);

            $project = $this->projectRepository->getByUuid($projectUuid);
            $project->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());
            $this->projectRepository->save($project);
        }
    }

    private function resolveCategory(string $categoryName): ProjectCategory
    {
        $displayName    = ProjectCategoryCatalog::normalizeDisplayName($categoryName);
        $normalizedName = ProjectCategoryCatalog::normalizeStorageValue($displayName);

        if ($displayName === '' || $normalizedName === '') {
            throw new ValueError('Project category must not be empty.');
        }

        $existingCategory = $this->projectCategoryRepository->findByNormalizedName($normalizedName);
        if ($existingCategory instanceof ProjectCategory) {
            return $existingCategory;
        }

        $now = DateAndTimeService::getDateTimeImmutable();

        $category = new ProjectCategory();
        $category->setUuid(Uuid::v7()->toRfc4122());
        $category->setName($displayName);
        $category->setNormalizedName($normalizedName);
        $category->setCreatedAt($now);
        $category->setUpdatedAt($now);

        $this->projectCategoryRepository->save($category);

        return $category;
    }

    private function validateProject(Project $project): void
    {
        if (trim($project->getTitle()) === '') {
            throw new ValueError('Project title must not be empty.');
        }

        if (trim($project->getCategoryUuid()) === '') {
            throw new ValueError('Project category must not be empty.');
        }
    }

    private function ensureTrackExists(string $trackUuid): void
    {
        if (!$this->trackManagementFacade->trackExists($trackUuid)) {
            throw new ValueError('Target track does not exist.');
        }
    }

    private function normalizeTitle(string $title): string
    {
        return preg_replace('/\s+/', ' ', trim($title)) ?? trim($title);
    }

    private function resequenceAssignments(string $projectUuid): void
    {
        $assignments        = $this->projectTrackAssignmentRepository->findByProjectUuid($projectUuid);
        $changedAssignments = [];

        foreach ($assignments as $index => $assignment) {
            $expectedPosition = $index + 1;

            if ($assignment->getPosition() === $expectedPosition) {
                continue;
            }

            $assignment->setPosition($expectedPosition);
            $assignment->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());
            $changedAssignments[] = $assignment;
        }

        if ($changedAssignments === []) {
            return;
        }

        $this->projectTrackAssignmentRepository->saveMany($changedAssignments);
    }

    private function normalizePerPage(int $perPage): int
    {
        return max(1, $perPage);
    }
}
