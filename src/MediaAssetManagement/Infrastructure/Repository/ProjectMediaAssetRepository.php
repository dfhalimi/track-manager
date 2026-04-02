<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Infrastructure\Repository;

use App\MediaAssetManagement\Domain\Entity\ProjectMediaAsset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectMediaAsset>
 */
class ProjectMediaAssetRepository extends ServiceEntityRepository implements ProjectMediaAssetRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectMediaAsset::class);
    }

    public function save(ProjectMediaAsset $projectMediaAsset): void
    {
        $this->getEntityManager()->persist($projectMediaAsset);
        $this->getEntityManager()->flush();
    }

    public function remove(ProjectMediaAsset $projectMediaAsset): void
    {
        $this->getEntityManager()->remove($projectMediaAsset);
        $this->getEntityManager()->flush();
    }

    public function findCurrentByProjectUuid(string $projectUuid): ?ProjectMediaAsset
    {
        /* @var ?ProjectMediaAsset */
        return $this->findOneBy(['projectUuid' => $projectUuid]);
    }
}
