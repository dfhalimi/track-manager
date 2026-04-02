<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Repository;

use App\ProjectManagement\Domain\Entity\ProjectCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ValueError;

/**
 * @extends ServiceEntityRepository<ProjectCategory>
 */
class ProjectCategoryRepository extends ServiceEntityRepository implements ProjectCategoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectCategory::class);
    }

    public function save(ProjectCategory $projectCategory): void
    {
        $this->getEntityManager()->persist($projectCategory);
        $this->getEntityManager()->flush();
    }

    public function getByUuid(string $categoryUuid): ProjectCategory
    {
        $category = $this->findByUuid($categoryUuid);

        if ($category === null) {
            throw new ValueError(sprintf('Project category with UUID "%s" was not found.', $categoryUuid));
        }

        return $category;
    }

    public function findByUuid(string $categoryUuid): ?ProjectCategory
    {
        /* @var ?ProjectCategory */
        return $this->find($categoryUuid);
    }

    public function findByNormalizedName(string $normalizedName): ?ProjectCategory
    {
        /* @var ?ProjectCategory */
        return $this->findOneBy(['normalizedName' => $normalizedName]);
    }

    public function findAllOrderedByName(): array
    {
        $result = $this->createQueryBuilder('category')
            ->orderBy('category.name', 'ASC')
            ->getQuery()
            ->getResult();

        if (!is_array($result)) {
            return [];
        }

        $categories = array_values(
            array_filter(
                $result,
                static fn (mixed $category): bool => $category instanceof ProjectCategory
            )
        );

        /* @var list<ProjectCategory> $categories */
        return $categories;
    }
}
