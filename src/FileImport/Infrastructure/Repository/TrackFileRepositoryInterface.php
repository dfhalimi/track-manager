<?php

declare(strict_types=1);

namespace App\FileImport\Infrastructure\Repository;

use App\FileImport\Domain\Entity\TrackFile;

interface TrackFileRepositoryInterface
{
    public function save(TrackFile $trackFile): void;

    public function remove(TrackFile $trackFile): void;

    public function findCurrentByTrackUuid(string $trackUuid): ?TrackFile;
}
