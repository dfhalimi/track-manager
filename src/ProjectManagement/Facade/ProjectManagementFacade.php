<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade;

use App\ProjectManagement\Domain\Entity\ProjectCategory;
use App\ProjectManagement\Domain\Entity\ProjectTrackAssignment;
use App\ProjectManagement\Domain\Service\ProjectManagementDomainServiceInterface;
use App\ProjectManagement\Facade\Dto\ProjectCategoryDto;
use App\ProjectManagement\Facade\Dto\ProjectDto;
use App\ProjectManagement\Facade\Dto\ProjectTrackAssignmentDto;
use App\ProjectManagement\Facade\Dto\TrackProjectMembershipDto;
use Throwable;

readonly class ProjectManagementFacade implements ProjectManagementFacadeInterface
{
    public function __construct(
        private ProjectManagementDomainServiceInterface $projectManagementDomainService
    ) {
    }

    public function getProjectByUuid(string $projectUuid): ProjectDto
    {
        $project  = $this->projectManagementDomainService->getProjectByUuid($projectUuid);
        $category = $this->projectManagementDomainService->getProjectCategoryByUuid($project->getCategoryUuid());

        return new ProjectDto(
            $project->getUuid(),
            $project->getTitle(),
            $project->getCategoryUuid(),
            $category->getName(),
            $project->isCancelled(),
            $project->isPublished(),
            $project->getPublishedAt(),
            $project->getCreatedAt(),
            $project->getUpdatedAt()
        );
    }

    public function projectExists(string $projectUuid): bool
    {
        try {
            $this->projectManagementDomainService->getProjectByUuid($projectUuid);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function getAllProjectCategories(): array
    {
        return array_map(
            static fn (ProjectCategory $category): ProjectCategoryDto => new ProjectCategoryDto(
                $category->getUuid(),
                $category->getName()
            ),
            $this->projectManagementDomainService->getAllProjectCategories()
        );
    }

    public function getTrackAssignmentsByProjectUuid(string $projectUuid): array
    {
        return array_map(
            static fn (ProjectTrackAssignment $assignment): ProjectTrackAssignmentDto => new ProjectTrackAssignmentDto(
                $assignment->getTrackUuid(),
                $assignment->getPosition()
            ),
            $this->projectManagementDomainService->getTrackAssignmentsByProjectUuid($projectUuid)
        );
    }

    public function getProjectsByTrackUuid(string $trackUuid): array
    {
        $memberships = [];

        foreach ($this->projectManagementDomainService->getTrackAssignmentsByTrackUuid($trackUuid) as $assignment) {
            $project = $this->projectManagementDomainService->getProjectByUuid($assignment->getProjectUuid());
            if ($project->isCancelled()) {
                continue;
            }

            $category = $this->projectManagementDomainService->getProjectCategoryByUuid($project->getCategoryUuid());

            $memberships[] = new TrackProjectMembershipDto(
                $project->getUuid(),
                $project->getTitle(),
                $category->getName(),
                $assignment->getPosition(),
                $project->isPublished()
            );
        }

        return $memberships;
    }

    public function removeTrackFromAllProjects(string $trackUuid): void
    {
        $this->projectManagementDomainService->removeTrackFromAllProjects($trackUuid);
    }

    public function removeTrackFromActiveProjects(string $trackUuid): void
    {
        $this->projectManagementDomainService->removeTrackFromActiveProjects($trackUuid);
    }
}
