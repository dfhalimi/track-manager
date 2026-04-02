<?php

declare(strict_types=1);

namespace App\TrackManagement\Infrastructure\Repository;

use App\TrackManagement\Domain\Entity\ChecklistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChecklistItem>
 */
class ChecklistItemRepository extends ServiceEntityRepository implements ChecklistItemRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChecklistItem::class);
    }

    public function save(ChecklistItem $item): void
    {
        $this->getEntityManager()->persist($item);
        $this->getEntityManager()->flush();
    }

    public function saveMany(array $items): void
    {
        foreach ($items as $item) {
            $this->getEntityManager()->persist($item);
        }

        $this->getEntityManager()->flush();
    }

    public function remove(ChecklistItem $item): void
    {
        $this->getEntityManager()->remove($item);
        $this->getEntityManager()->flush();
    }

    public function findByUuid(string $itemUuid): ?ChecklistItem
    {
        /* @var ?ChecklistItem */
        return $this->find($itemUuid);
    }

    public function findByTrackUuid(string $trackUuid): array
    {
        $result = $this->createQueryBuilder('item')
            ->andWhere('item.trackUuid = :trackUuid')
            ->setParameter('trackUuid', $trackUuid)
            ->orderBy('item.position', 'ASC')
            ->getQuery()
            ->getResult();

        if (!is_array($result)) {
            return [];
        }

        $items = array_values(
            array_filter(
                $result,
                static fn (mixed $item): bool => $item instanceof ChecklistItem
            )
        );

        /* @var list<ChecklistItem> $items */
        return $items;
    }

    public function countByTrackUuid(string $trackUuid): int
    {
        return (int) $this->createQueryBuilder('item')
            ->select('COUNT(item.uuid)')
            ->andWhere('item.trackUuid = :trackUuid')
            ->setParameter('trackUuid', $trackUuid)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getNextPositionForTrack(string $trackUuid): int
    {
        $result = $this->createQueryBuilder('item')
            ->select('MAX(item.position) AS maxPosition')
            ->andWhere('item.trackUuid = :trackUuid')
            ->setParameter('trackUuid', $trackUuid)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $result) + 1;
    }

    public function removeAllByTrackUuid(string $trackUuid): void
    {
        $this->createQueryBuilder('item')
            ->delete()
            ->andWhere('item.trackUuid = :trackUuid')
            ->setParameter('trackUuid', $trackUuid)
            ->getQuery()
            ->execute();
    }
}
