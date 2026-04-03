<?php

declare(strict_types=1);

namespace App\ActivityHistory\Infrastructure\Repository;

use App\ActivityHistory\Domain\Entity\ActivityHistoryEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityHistoryEntry>
 */
class ActivityHistoryEntryRepository extends ServiceEntityRepository implements ActivityHistoryEntryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityHistoryEntry::class);
    }

    public function save(ActivityHistoryEntry $entry): void
    {
        $this->getEntityManager()->persist($entry);
        $this->getEntityManager()->flush();
    }

    public function findByEntity(string $entityType, string $entityUuid, int $limit): array
    {
        $result = $this->createQueryBuilder('entry')
            ->andWhere('entry.entityType = :entityType')
            ->andWhere('entry.entityUuid = :entityUuid')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityUuid', $entityUuid)
            ->orderBy('entry.occurredAt', 'DESC')
            ->addOrderBy('entry.uuid', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();

        if (!is_array($result)) {
            return [];
        }

        return array_values(
            array_filter(
                $result,
                static fn (mixed $entry): bool => $entry instanceof ActivityHistoryEntry
            )
        );
    }
}
