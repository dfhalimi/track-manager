<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Repository;

use App\ProjectManagement\Domain\Entity\ProjectTrackAssignment;

interface ProjectTrackAssignmentRepositoryInterface
{
    public function save(ProjectTrackAssignment $assignment): void;

    /**
     * @param list<ProjectTrackAssignment> $assignments
     */
    public function saveMany(array $assignments): void;

    public function remove(ProjectTrackAssignment $assignment): void;

    public function findByProjectUuidAndTrackUuid(string $projectUuid, string $trackUuid): ?ProjectTrackAssignment;

    /**
     * @return list<ProjectTrackAssignment>
     */
    public function findByProjectUuid(string $projectUuid): array;

    /**
     * @return list<ProjectTrackAssignment>
     */
    public function findByTrackUuid(string $trackUuid): array;

    public function getNextPositionForProject(string $projectUuid): int;

    public function removeAllByProjectUuid(string $projectUuid): void;

    public function removeAllByTrackUuid(string $trackUuid): void;
}
