<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Repository;

use App\ProjectManagement\Domain\Dto\ProjectListFilterDto;
use App\ProjectManagement\Domain\Entity\Project;
use App\ProjectManagement\Domain\Entity\ProjectCategory;
use App\ProjectManagement\Domain\Support\ProjectCategoryCatalog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ValueError;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository implements ProjectRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function save(Project $project): void
    {
        $this->getEntityManager()->persist($project);
        $this->getEntityManager()->flush();
    }

    public function remove(Project $project): void
    {
        $this->getEntityManager()->remove($project);
        $this->getEntityManager()->flush();
    }

    public function getByUuid(string $projectUuid): Project
    {
        $project = $this->findByUuid($projectUuid);

        if ($project === null) {
            throw new ValueError(sprintf('Project with UUID "%s" was not found.', $projectUuid));
        }

        return $project;
    }

    public function findByUuid(string $projectUuid): ?Project
    {
        /* @var ?Project */
        return $this->find($projectUuid);
    }

    public function findByNormalizedTitle(string $normalizedTitle): ?Project
    {
        $queryBuilder = $this->createQueryBuilder('project')
            ->andWhere('project.normalizedTitle = :normalizedTitle')
            ->setParameter('normalizedTitle', $normalizedTitle)
            ->setMaxResults(1);

        $project = $queryBuilder->getQuery()->getOneOrNullResult();

        return $project instanceof Project ? $project : null;
    }

    public function findAllByFilter(ProjectListFilterDto $filter): array
    {
        $queryBuilder = $this->createQueryBuilder('project')
            ->leftJoin(ProjectCategory::class, 'category', 'WITH', 'category.uuid = project.categoryUuid');

        $searchQuery = trim((string) ($filter->searchQuery ?? ''));
        if ($searchQuery !== '') {
            $queryBuilder
                ->andWhere('project.title LIKE :query OR category.name LIKE :query')
                ->setParameter('query', '%' . $searchQuery . '%');
        }

        $categoryFilter = trim((string) ($filter->categoryFilter ?? ''));
        if ($categoryFilter !== '') {
            $queryBuilder
                ->andWhere('category.normalizedName = :categoryFilter')
                ->setParameter('categoryFilter', ProjectCategoryCatalog::normalizeStorageValue($categoryFilter));
        }

        $sortBy = match ($filter->sortBy) {
            'title'     => 'project.title',
            'createdAt' => 'project.createdAt',
            default     => 'project.updatedAt',
        };

        $sortDirection = strtoupper((string) ($filter->sortDirection ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $result = $queryBuilder
            ->orderBy($sortBy, $sortDirection)
            ->getQuery()
            ->getResult();

        if (!is_array($result)) {
            return [];
        }

        $projects = array_values(
            array_filter(
                $result,
                static fn (mixed $project): bool => $project instanceof Project
            )
        );

        /* @var list<Project> $projects */
        return $projects;
    }
}
