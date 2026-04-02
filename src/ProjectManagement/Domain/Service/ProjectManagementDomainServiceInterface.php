<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Service;

use App\ProjectManagement\Domain\Dto\AddTrackToProjectInputDto;
use App\ProjectManagement\Domain\Dto\CreateProjectInputDto;
use App\ProjectManagement\Domain\Dto\ProjectListFilterDto;
use App\ProjectManagement\Domain\Dto\ProjectListResultDto;
use App\ProjectManagement\Domain\Dto\RemoveTrackFromProjectInputDto;
use App\ProjectManagement\Domain\Dto\ReorderProjectTracksInputDto;
use App\ProjectManagement\Domain\Dto\UpdateProjectInputDto;
use App\ProjectManagement\Domain\Entity\Project;
use App\ProjectManagement\Domain\Entity\ProjectCategory;
use App\ProjectManagement\Domain\Entity\ProjectTrackAssignment;

interface ProjectManagementDomainServiceInterface
{
    public function createProject(CreateProjectInputDto $input): Project;

    public function updateProject(UpdateProjectInputDto $input): Project;

    public function deleteProject(string $projectUuid): void;

    public function cancelProject(string $projectUuid): Project;

    public function reactivateProject(string $projectUuid): Project;

    public function getProjectByUuid(string $projectUuid): Project;

    /**
     * @return list<ProjectCategory>
     */
    public function getAllProjectCategories(): array;

    public function getProjectCategoryByUuid(string $categoryUuid): ProjectCategory;

    public function getAllProjects(ProjectListFilterDto $filter): ProjectListResultDto;

    /**
     * @return list<string>
     */
    public function getProjectSearchSuggestions(ProjectListFilterDto $filter, int $limit): array;

    /**
     * @return list<ProjectTrackAssignment>
     */
    public function getTrackAssignmentsByProjectUuid(string $projectUuid): array;

    /**
     * @return list<ProjectTrackAssignment>
     */
    public function getTrackAssignmentsByTrackUuid(string $trackUuid): array;

    public function addTrackToProject(AddTrackToProjectInputDto $input): ProjectTrackAssignment;

    public function removeTrackFromProject(RemoveTrackFromProjectInputDto $input): void;

    public function reorderProjectTracks(ReorderProjectTracksInputDto $input): void;

    public function removeTrackFromAllProjects(string $trackUuid): void;

    public function removeTrackFromActiveProjects(string $trackUuid): void;
}
