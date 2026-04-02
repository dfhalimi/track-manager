<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Repository;

use App\ProjectManagement\Domain\Entity\ProjectTrackAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectTrackAssignment>
 */
class ProjectTrackAssignmentRepository extends ServiceEntityRepository implements ProjectTrackAssignmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectTrackAssignment::class);
    }

    public function save(ProjectTrackAssignment $assignment): void
    {
        $this->getEntityManager()->persist($assignment);
        $this->getEntityManager()->flush();
    }

    public function saveMany(array $assignments): void
    {
        foreach ($assignments as $assignment) {
            $this->getEntityManager()->persist($assignment);
        }

        $this->getEntityManager()->flush();
    }

    public function remove(ProjectTrackAssignment $assignment): void
    {
        $this->getEntityManager()->remove($assignment);
        $this->getEntityManager()->flush();
    }

    public function findByProjectUuidAndTrackUuid(string $projectUuid, string $trackUuid): ?ProjectTrackAssignment
    {
        /* @var ?ProjectTrackAssignment */
        return $this->findOneBy(['projectUuid' => $projectUuid, 'trackUuid' => $trackUuid]);
    }

    public function findByProjectUuid(string $projectUuid): array
    {
        $result = $this->createQueryBuilder('assignment')
            ->andWhere('assignment.projectUuid = :projectUuid')
            ->setParameter('projectUuid', $projectUuid)
            ->orderBy('assignment.position', 'ASC')
            ->getQuery()
            ->getResult();

        if (!is_array($result)) {
            return [];
        }

        $assignments = array_values(
            array_filter(
                $result,
                static fn (mixed $assignment): bool => $assignment instanceof ProjectTrackAssignment
            )
        );

        /* @var list<ProjectTrackAssignment> $assignments */
        return $assignments;
    }

    public function findByTrackUuid(string $trackUuid): array
    {
        $result = $this->createQueryBuilder('assignment')
            ->andWhere('assignment.trackUuid = :trackUuid')
            ->setParameter('trackUuid', $trackUuid)
            ->orderBy('assignment.position', 'ASC')
            ->getQuery()
            ->getResult();

        if (!is_array($result)) {
            return [];
        }

        $assignments = array_values(
            array_filter(
                $result,
                static fn (mixed $assignment): bool => $assignment instanceof ProjectTrackAssignment
            )
        );

        /* @var list<ProjectTrackAssignment> $assignments */
        return $assignments;
    }

    public function getNextPositionForProject(string $projectUuid): int
    {
        $result = $this->createQueryBuilder('assignment')
            ->select('MAX(assignment.position) AS maxPosition')
            ->andWhere('assignment.projectUuid = :projectUuid')
            ->setParameter('projectUuid', $projectUuid)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $result) + 1;
    }

    public function removeAllByProjectUuid(string $projectUuid): void
    {
        foreach ($this->findByProjectUuid($projectUuid) as $assignment) {
            $this->getEntityManager()->remove($assignment);
        }

        $this->getEntityManager()->flush();
    }

    public function removeAllByTrackUuid(string $trackUuid): void
    {
        foreach ($this->findByTrackUuid($trackUuid) as $assignment) {
            $this->getEntityManager()->remove($assignment);
        }

        $this->getEntityManager()->flush();
    }
}
