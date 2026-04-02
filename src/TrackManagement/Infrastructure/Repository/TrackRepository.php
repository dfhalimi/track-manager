<?php

declare(strict_types=1);

namespace App\TrackManagement\Infrastructure\Repository;

use App\TrackManagement\Domain\Dto\TrackListFilterDto;
use App\TrackManagement\Domain\Entity\Track;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ValueError;

/**
 * @extends ServiceEntityRepository<Track>
 */
class TrackRepository extends ServiceEntityRepository implements TrackRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Track::class);
    }

    public function save(Track $track): void
    {
        $this->getEntityManager()->persist($track);
        $this->getEntityManager()->flush();
    }

    public function remove(Track $track): void
    {
        $this->getEntityManager()->remove($track);
        $this->getEntityManager()->flush();
    }

    public function getByUuid(string $trackUuid): Track
    {
        $track = $this->findByUuid($trackUuid);

        if ($track === null) {
            throw new ValueError(sprintf('Track with UUID "%s" was not found.', $trackUuid));
        }

        return $track;
    }

    public function findByUuid(string $trackUuid): ?Track
    {
        /* @var ?Track */
        return $this->find($trackUuid);
    }

    public function findByTrackNumber(int $trackNumber): ?Track
    {
        /* @var ?Track */
        return $this->findOneBy(['trackNumber' => $trackNumber]);
    }

    public function findAllByFilter(TrackListFilterDto $filter): array
    {
        $queryBuilder = $this->createQueryBuilder('track');

        $searchQuery = trim((string) ($filter->searchQuery ?? ''));
        if ($searchQuery !== '') {
            if (ctype_digit($searchQuery)) {
                $queryBuilder
                    ->andWhere('track.trackNumber = :trackNumber OR track.title LIKE :query OR track.beatName LIKE :query OR track.publishingName LIKE :query')
                    ->setParameter('trackNumber', (int) $searchQuery)
                    ->setParameter('query', '%' . $searchQuery . '%');
            } else {
                $queryBuilder
                    ->andWhere('track.title LIKE :query OR track.beatName LIKE :query OR track.publishingName LIKE :query')
                    ->setParameter('query', '%' . $searchQuery . '%');
            }
        }

        $sortBy = match ($filter->sortBy) {
            'trackNumber' => 'track.trackNumber',
            'createdAt'   => 'track.createdAt',
            'title'       => 'track.title',
            default       => 'track.updatedAt',
        };

        $sortDirection = strtoupper($filter->sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        $queryBuilder->orderBy($sortBy, $sortDirection);

        $result = $queryBuilder->getQuery()->getResult();
        if (!is_array($result)) {
            return [];
        }

        $tracks = array_values(
            array_filter(
                $result,
                static fn (mixed $track): bool => $track instanceof Track
            )
        );

        /* @var list<Track> $tracks */
        return $tracks;
    }

    public function getNextTrackNumber(): int
    {
        $result = $this->createQueryBuilder('track')
            ->select('MAX(track.trackNumber) AS maxTrackNumber')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $result) + 1;
    }
}
