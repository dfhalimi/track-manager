<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\ProjectManagement\Domain\Entity\ProjectCategory;
use App\ProjectManagement\Domain\Service\ProjectManagementDomainServiceInterface;
use App\ProjectManagement\Presentation\Dto\ProjectFormViewDto;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class ProjectFormPresentationService implements ProjectFormPresentationServiceInterface
{
    public function __construct(
        private ProjectManagementDomainServiceInterface $projectManagementDomainService,
        private UrlGeneratorInterface                   $urlGenerator
    ) {
    }

    public function buildCreateFormViewDto(
        ?string $title = null,
        ?string $categoryName = null
    ): ProjectFormViewDto {
        return new ProjectFormViewDto(
            null,
            (string) ($title ?? ''),
            (string) ($categoryName ?? 'Single'),
            $this->buildCategoryOptions(),
            $this->urlGenerator->generate('project_management.presentation.create'),
            $this->urlGenerator->generate('project_management.presentation.index'),
            'Projekt erstellen',
            false
        );
    }

    public function buildEditFormViewDto(
        string  $projectUuid,
        ?string $title = null,
        ?string $categoryName = null
    ): ProjectFormViewDto {
        $project  = $this->projectManagementDomainService->getProjectByUuid($projectUuid);
        $category = $this->projectManagementDomainService->getProjectCategoryByUuid($project->getCategoryUuid());

        return new ProjectFormViewDto(
            $project->getUuid(),
            (string) ($title ?? $project->getTitle()),
            (string) ($categoryName ?? $category->getName()),
            $this->buildCategoryOptions(),
            $this->urlGenerator->generate('project_management.presentation.edit', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.show', ['projectUuid' => $projectUuid]),
            'Projekt speichern',
            true
        );
    }

    /**
     * @return list<string>
     */
    private function buildCategoryOptions(): array
    {
        return array_map(
            static fn (ProjectCategory $category): string => $category->getName(),
            $this->projectManagementDomainService->getAllProjectCategories()
        );
    }
}
