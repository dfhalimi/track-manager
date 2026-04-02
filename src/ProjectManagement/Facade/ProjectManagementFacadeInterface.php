<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade;

use App\ProjectManagement\Facade\Dto\ProjectCategoryDto;
use App\ProjectManagement\Facade\Dto\ProjectDto;
use App\ProjectManagement\Facade\Dto\ProjectTrackAssignmentDto;
use App\ProjectManagement\Facade\Dto\TrackProjectMembershipDto;

interface ProjectManagementFacadeInterface
{
    public function getProjectByUuid(string $projectUuid): ProjectDto;

    public function projectExists(string $projectUuid): bool;

    /**
     * @return list<ProjectCategoryDto>
     */
    public function getAllProjectCategories(): array;

    /**
     * @return list<ProjectTrackAssignmentDto>
     */
    public function getTrackAssignmentsByProjectUuid(string $projectUuid): array;

    /**
     * @return list<TrackProjectMembershipDto>
     */
    public function getProjectsByTrackUuid(string $trackUuid): array;

    public function removeTrackFromAllProjects(string $trackUuid): void;

    public function removeTrackFromActiveProjects(string $trackUuid): void;
}
