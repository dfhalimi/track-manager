<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Service;

use App\Common\Service\LocalizedDateTimeServiceInterface;
use App\ProjectManagement\Domain\Dto\AddTrackToProjectInputDto;
use App\ProjectManagement\Domain\Dto\CreateProjectInputDto;
use App\ProjectManagement\Domain\Dto\ProjectListFilterDto;
use App\ProjectManagement\Domain\Dto\ProjectListItemDto;
use App\ProjectManagement\Domain\Dto\ProjectListResultDto;
use App\ProjectManagement\Domain\Dto\PublishProjectInputDto;
use App\ProjectManagement\Domain\Dto\RemoveTrackFromProjectInputDto;
use App\ProjectManagement\Domain\Dto\ReorderProjectTracksInputDto;
use App\ProjectManagement\Domain\Dto\UpdateProjectInputDto;
use App\ProjectManagement\Domain\Entity\Project;
use App\ProjectManagement\Domain\Entity\ProjectCategory;
use App\ProjectManagement\Domain\Entity\ProjectTrackAssignment;
use App\ProjectManagement\Domain\Support\ProjectCategoryCatalog;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectCancelledSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectCreatedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectPublishedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectReactivatedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectTracksReorderedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectUnpublishedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\ProjectUpdatedSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\TrackAssignedToProjectSymfonyEvent;
use App\ProjectManagement\Facade\SymfonyEvent\TrackRemovedFromProjectSymfonyEvent;
use App\ProjectManagement\Infrastructure\Repository\ProjectCategoryRepositoryInterface;
use App\ProjectManagement\Infrastructure\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Infrastructure\Repository\ProjectTrackAssignmentRepositoryInterface;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use DateTimeImmutable;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use ValueError;

readonly class ProjectManagementDomainService implements ProjectManagementDomainServiceInterface
{
    public function __construct(
        private ProjectRepositoryInterface                $projectRepository,
        private ProjectCategoryRepositoryInterface        $projectCategoryRepository,
        private ProjectTrackAssignmentRepositoryInterface $projectTrackAssignmentRepository,
        private TrackManagementFacadeInterface            $trackManagementFacade,
        private LocalizedDateTimeServiceInterface         $localizedDateTimeService,
        private EventDispatcherInterface                  $eventDispatcher
    ) {
    }

    public function createProject(CreateProjectInputDto $input): Project
    {
        $now = DateAndTimeService::getDateTimeImmutable();

        $project = new Project();
        $project->setUuid(Uuid::v7()->toRfc4122());
        $project->setTitle($this->normalizeTitle($input->title));
        $project->setNormalizedTitle($this->normalizeStorageTitle($input->title));
        $project->setCategoryUuid($this->resolveCategory($input->categoryName)->getUuid());
        $project->setArtists($this->normalizeArtists($input->artists));
        $project->setCancelled(false);
        $project->setPublished(false);
        $project->setPublishedAt(null);
        $project->setCreatedAt($now);
        $project->setUpdatedAt($now);

        $this->validateProject($project);
        $this->projectRepository->save($project);
        $this->eventDispatcher->dispatch(
            new ProjectCreatedSymfonyEvent(
                $project->getUuid(),
                [
                    sprintf('Titel: %s', $project->getTitle()),
                    sprintf('Kategorie: %s', $this->projectCategoryRepository->getByUuid($project->getCategoryUuid())->getName()),
                    sprintf('Interpreten: %s', $this->formatArtists($project->getArtists())),
                ],
                $now
            )
        );

        return $project;
    }

    public function updateProject(UpdateProjectInputDto $input): Project
    {
        $project = $this->projectRepository->getByUuid($input->projectUuid);
        $this->assertProjectIsActive($project);
        $before = $this->captureProjectSnapshot($project);

        $project->setTitle($this->normalizeTitle($input->title));
        $project->setNormalizedTitle($this->normalizeStorageTitle($input->title));
        $project->setCategoryUuid($this->resolveCategory($input->categoryName)->getUuid());
        $project->setArtists($this->normalizeArtists($input->artists));
        if ($project->isPublished()) {
            $project->setPublishedAt($this->normalizePublishedAt($input->publishedAt));
        }
        $project->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

        $this->validateProject($project);
        $this->projectRepository->save($project);
        $changes = $this->buildProjectChangeDetails($before, $project);
        if ($changes !== []) {
            $this->eventDispatcher->dispatch(
                new ProjectUpdatedSymfonyEvent(
                    $project->getUuid(),
                    $changes,
                    $project->getUpdatedAt()
                )
            );
        }

        return $project;
    }

    public function deleteProject(string $projectUuid): void
    {
        $project = $this->projectRepository->getByUuid($projectUuid);
        $this->projectTrackAssignmentRepository->removeAllByProjectUuid($projectUuid);
        $this->projectRepository->remove($project);
    }

    public function cancelProject(string $projectUuid): Project
    {
        $project = $this->projectRepository->getByUuid($projectUuid);
        if ($project->isCancelled()) {
            return $project;
        }

        $project->setCancelled(true);
        $project->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());
        $this->projectRepository->save($project);
        $this->eventDispatcher->dispatch(
            new ProjectCancelledSymfonyEvent(
                $project->getUuid(),
                $project->getUpdatedAt()
            )
        );

        return $project;
    }

    public function reactivateProject(string $projectUuid): Project
    {
        $project = $this->projectRepository->getByUuid($projectUuid);
        if (!$project->isCancelled()) {
            return $project;
        }

        $this->validateProject($project);
        $project->setCancelled(false);
        $project->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());
        $this->projectRepository->save($project);
        $this->eventDispatcher->dispatch(
            new ProjectReactivatedSymfonyEvent(
                $project->getUuid(),
                $project->getUpdatedAt()
            )
        );

        return $project;
    }

    public function publishProject(PublishProjectInputDto $input): Project
    {
        $project = $this->projectRepository->getByUuid($input->projectUuid);
        $this->assertProjectPublicationIsEditable($project);

        if ($project->isPublished()) {
            return $project;
        }

        $occurredAt  = DateAndTimeService::getDateTimeImmutable();
        $publishedAt = $this->normalizePublishedAt($input->publishedAt);

        $project->setPublished(true);
        $project->setPublishedAt($publishedAt);
        $project->setUpdatedAt($occurredAt);
        $this->projectRepository->save($project);
        $this->eventDispatcher->dispatch(
            new ProjectPublishedSymfonyEvent(
                $project->getUuid(),
                $publishedAt,
                $occurredAt
            )
        );

        return $project;
    }

    public function unpublishProject(string $projectUuid): Project
    {
        $project = $this->projectRepository->getByUuid($projectUuid);
        $this->assertProjectPublicationIsEditable($project);

        if (!$project->isPublished()) {
            return $project;
        }

        $now = DateAndTimeService::getDateTimeImmutable();

        $project->setPublished(false);
        $project->setPublishedAt(null);
        $project->setUpdatedAt($now);
        $this->projectRepository->save($project);
        $this->eventDispatcher->dispatch(
            new ProjectUnpublishedSymfonyEvent(
                $project->getUuid(),
                $now
            )
        );

        return $project;
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
                $project->getArtists(),
                $project->isCancelled(),
                $project->isPublished(),
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
        $this->assertProjectIsActive($project);
        $this->ensureTrackIsActive($input->trackUuid);

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
        $this->eventDispatcher->dispatch(
            new TrackAssignedToProjectSymfonyEvent(
                $input->projectUuid,
                $input->trackUuid,
                $assignment->getPosition(),
                $now
            )
        );

        return $assignment;
    }

    public function removeTrackFromProject(RemoveTrackFromProjectInputDto $input): void
    {
        $project = $this->projectRepository->getByUuid($input->projectUuid);
        $this->assertProjectIsActive($project);
        $assignment = $this->projectTrackAssignmentRepository->findByProjectUuidAndTrackUuid($input->projectUuid, $input->trackUuid);

        if ($assignment === null) {
            return;
        }

        $occurredAt = DateAndTimeService::getDateTimeImmutable();

        $this->projectTrackAssignmentRepository->remove($assignment);
        $this->resequenceAssignments($input->projectUuid);
        $project->setUpdatedAt($occurredAt);
        $this->projectRepository->save($project);
        $this->eventDispatcher->dispatch(
            new TrackRemovedFromProjectSymfonyEvent(
                $input->projectUuid,
                $input->trackUuid,
                $occurredAt
            )
        );
    }

    public function reorderProjectTracks(ReorderProjectTracksInputDto $input): void
    {
        $project = $this->projectRepository->getByUuid($input->projectUuid);
        $this->assertProjectIsActive($project);
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

        $beforeTrackOrder = array_map(
            static fn (ProjectTrackAssignment $assignment): string => $assignment->getTrackUuid(),
            $assignments
        );
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

        $occurredAt = DateAndTimeService::getDateTimeImmutable();

        $this->projectTrackAssignmentRepository->saveMany($reorderedAssignments);
        $project->setUpdatedAt($occurredAt);
        $this->projectRepository->save($project);
        if ($beforeTrackOrder !== $orderedTrackUuids) {
            $this->eventDispatcher->dispatch(
                new ProjectTracksReorderedSymfonyEvent(
                    $input->projectUuid,
                    $orderedTrackUuids,
                    $occurredAt
                )
            );
        }
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

        $occurredAt = DateAndTimeService::getDateTimeImmutable();

        $this->projectTrackAssignmentRepository->removeAllByTrackUuid($trackUuid);

        foreach ($affectedProjectUuids as $projectUuid) {
            $this->resequenceAssignments($projectUuid);

            $project = $this->projectRepository->getByUuid($projectUuid);
            $project->setUpdatedAt($occurredAt);
            $this->projectRepository->save($project);
            $this->eventDispatcher->dispatch(
                new TrackRemovedFromProjectSymfonyEvent(
                    $projectUuid,
                    $trackUuid,
                    $occurredAt
                )
            );
        }
    }

    public function removeTrackFromActiveProjects(string $trackUuid): void
    {
        $assignments          = $this->projectTrackAssignmentRepository->findByTrackUuid($trackUuid);
        $affectedProjectUuids = [];

        foreach ($assignments as $assignment) {
            $project = $this->projectRepository->getByUuid($assignment->getProjectUuid());
            if ($project->isCancelled()) {
                continue;
            }

            $occurredAt = DateAndTimeService::getDateTimeImmutable();

            $this->projectTrackAssignmentRepository->remove($assignment);
            $affectedProjectUuids[] = $assignment->getProjectUuid();
            $this->eventDispatcher->dispatch(
                new TrackRemovedFromProjectSymfonyEvent(
                    $assignment->getProjectUuid(),
                    $trackUuid,
                    $occurredAt
                )
            );
        }

        foreach (array_values(array_unique($affectedProjectUuids)) as $projectUuid) {
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

        $existingProject = $this->projectRepository->findByNormalizedTitle($project->getNormalizedTitle());
        if ($existingProject instanceof Project && $existingProject->getUuid() !== $project->getUuid()) {
            throw new ValueError('Es existiert bereits ein Projekt mit diesem Namen.');
        }
    }

    private function ensureTrackExists(string $trackUuid): void
    {
        if (!$this->trackManagementFacade->trackExists($trackUuid)) {
            throw new ValueError('Target track does not exist.');
        }
    }

    private function ensureTrackIsActive(string $trackUuid): void
    {
        $this->ensureTrackExists($trackUuid);

        if ($this->trackManagementFacade->getTrackByUuid($trackUuid)->cancelled) {
            throw new ValueError('Archivierte Tracks koennen nicht zu Projekten hinzugefuegt werden.');
        }
    }

    private function assertProjectIsActive(Project $project): void
    {
        if ($project->isCancelled()) {
            throw new ValueError('Archivierte Projekte koennen nicht bearbeitet werden.');
        }
    }

    private function assertProjectPublicationIsEditable(Project $project): void
    {
        if ($project->isCancelled()) {
            throw new ValueError('Archivierte Projekte koennen nicht veroeffentlicht oder ent-veroeffentlicht werden.');
        }
    }

    private function normalizeTitle(string $title): string
    {
        return preg_replace('/\s+/', ' ', trim($title)) ?? trim($title);
    }

    private function normalizeStorageTitle(string $title): string
    {
        return mb_strtolower($this->normalizeTitle($title));
    }

    /**
     * @param list<string> $artists
     *
     * @return list<string>
     */
    private function normalizeArtists(array $artists): array
    {
        $normalizedArtists = [];
        $knownArtists      = [];

        foreach ($artists as $artist) {
            $normalizedArtist = $this->normalizeTitle($artist);
            if ($normalizedArtist === '') {
                continue;
            }

            $normalizedStorageArtist = mb_strtolower($normalizedArtist);
            if (array_key_exists($normalizedStorageArtist, $knownArtists)) {
                continue;
            }

            $knownArtists[$normalizedStorageArtist] = true;
            $normalizedArtists[]                    = $normalizedArtist;
        }

        return $normalizedArtists;
    }

    /**
     * @return array{
     *     title: string,
     *     categoryName: string,
     *     artists: string,
     *     publishedAt: string
     * }
     */
    private function captureProjectSnapshot(Project $project): array
    {
        return [
            'title'        => $project->getTitle(),
            'categoryName' => $this->projectCategoryRepository->getByUuid($project->getCategoryUuid())->getName(),
            'artists'      => $this->formatArtists($project->getArtists()),
            'publishedAt'  => $this->formatPublishedAt($project->getPublishedAt()),
        ];
    }

    /**
     * @param array{
     *     title: string,
     *     categoryName: string,
     *     artists: string,
     *     publishedAt: string
     * } $before
     *
     * @return list<string>
     */
    private function buildProjectChangeDetails(array $before, Project $project): array
    {
        $after = $this->captureProjectSnapshot($project);

        $labels = [
            'title'        => 'Titel',
            'categoryName' => 'Kategorie',
            'artists'      => 'Interpreten',
            'publishedAt'  => 'Veröffentlicht am',
        ];

        $changes = [];
        foreach ($labels as $field => $label) {
            if ($before[$field] === $after[$field]) {
                continue;
            }

            $changes[] = sprintf(
                '%s: %s -> %s',
                $label,
                $this->normalizeHistoryValue($before[$field]),
                $this->normalizeHistoryValue($after[$field])
            );
        }

        return $changes;
    }

    /**
     * @param list<string> $artists
     */
    private function formatArtists(array $artists): string
    {
        return $artists === [] ? '—' : implode(', ', $artists);
    }

    private function normalizeHistoryValue(string $value): string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? '—' : $trimmed;
    }

    private function normalizePublishedAt(?DateTimeImmutable $publishedAt): DateTimeImmutable
    {
        if (!$publishedAt instanceof DateTimeImmutable) {
            throw new ValueError('Bitte gib ein Veröffentlichungsdatum an.');
        }

        if ($publishedAt > DateAndTimeService::getDateTimeImmutable()) {
            throw new ValueError('Das Veröffentlichungsdatum darf nicht in der Zukunft liegen.');
        }

        return $publishedAt;
    }

    private function formatPublishedAt(?DateTimeImmutable $publishedAt): string
    {
        return $publishedAt === null ? '—' : $this->localizedDateTimeService->formatForDisplay($publishedAt);
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
