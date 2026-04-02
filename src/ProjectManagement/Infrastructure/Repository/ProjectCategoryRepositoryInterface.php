<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Repository;

use App\ProjectManagement\Domain\Entity\ProjectCategory;

interface ProjectCategoryRepositoryInterface
{
    public function save(ProjectCategory $projectCategory): void;

    public function getByUuid(string $categoryUuid): ProjectCategory;

    public function findByUuid(string $categoryUuid): ?ProjectCategory;

    public function findByNormalizedName(string $normalizedName): ?ProjectCategory;

    /**
     * @return list<ProjectCategory>
     */
    public function findAllOrderedByName(): array;
}
