<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Infrastructure\Repository;

use App\MediaAssetManagement\Domain\Entity\ProjectMediaAsset;

interface ProjectMediaAssetRepositoryInterface
{
    public function save(ProjectMediaAsset $projectMediaAsset): void;

    public function remove(ProjectMediaAsset $projectMediaAsset): void;

    public function findCurrentByProjectUuid(string $projectUuid): ?ProjectMediaAsset;
}
