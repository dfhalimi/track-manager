<?php

declare(strict_types=1);

namespace App\FileImport\Infrastructure\Repository;

use App\FileImport\Domain\Entity\TrackFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrackFile>
 */
class TrackFileRepository extends ServiceEntityRepository implements TrackFileRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackFile::class);
    }

    public function save(TrackFile $trackFile): void
    {
        $this->getEntityManager()->persist($trackFile);
        $this->getEntityManager()->flush();
    }

    public function remove(TrackFile $trackFile): void
    {
        $this->getEntityManager()->remove($trackFile);
        $this->getEntityManager()->flush();
    }

    public function findCurrentByTrackUuid(string $trackUuid): ?TrackFile
    {
        /* @var ?TrackFile */
        return $this->findOneBy(['trackUuid' => $trackUuid]);
    }
}
