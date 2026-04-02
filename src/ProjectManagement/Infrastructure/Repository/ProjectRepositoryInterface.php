<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Repository;

use App\ProjectManagement\Domain\Dto\ProjectListFilterDto;
use App\ProjectManagement\Domain\Entity\Project;

interface ProjectRepositoryInterface
{
    public function save(Project $project): void;

    public function remove(Project $project): void;

    public function getByUuid(string $projectUuid): Project;

    public function findByUuid(string $projectUuid): ?Project;

    /**
     * @return list<Project>
     */
    public function findAllByFilter(ProjectListFilterDto $filter): array;
}
