<?php

declare(strict_types=1);

namespace App\TrackManagement\Infrastructure\Repository;

use App\TrackManagement\Domain\Dto\TrackListFilterDto;
use App\TrackManagement\Domain\Entity\Track;

interface TrackRepositoryInterface
{
    public function save(Track $track): void;

    public function remove(Track $track): void;

    public function getByUuid(string $trackUuid): Track;

    public function findByUuid(string $trackUuid): ?Track;

    public function findByTrackNumber(int $trackNumber): ?Track;

    /**
     * @return list<Track>
     */
    public function findAllByFilter(TrackListFilterDto $filter): array;

    public function getNextTrackNumber(): int;
}
