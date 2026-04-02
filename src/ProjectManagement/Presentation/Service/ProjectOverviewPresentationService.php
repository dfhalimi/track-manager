<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\ProjectManagement\Domain\Dto\ProjectListFilterDto;
use App\ProjectManagement\Domain\Service\ProjectManagementDomainServiceInterface;
use App\ProjectManagement\Presentation\Dto\ProjectListItemViewDto;
use App\ProjectManagement\Presentation\Dto\ProjectListViewDto;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class ProjectOverviewPresentationService implements ProjectOverviewPresentationServiceInterface
{
    public function __construct(
        private ProjectManagementDomainServiceInterface $projectManagementDomainService,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function buildProjectListViewDto(
        ?string $searchQuery,
        ?string $categoryFilter,
        ?string $sortBy,
        ?string $sortDirection
    ): ProjectListViewDto {
        $result = $this->projectManagementDomainService->getAllProjects(
            new ProjectListFilterDto(
                $searchQuery,
                $categoryFilter,
                $sortBy ?? 'updatedAt',
                $sortDirection ?? 'DESC'
            )
        );

        $items = array_map(
            fn ($item): ProjectListItemViewDto => new ProjectListItemViewDto(
                $item->uuid,
                $item->title,
                $item->categoryName,
                $item->trackCount,
                $this->urlGenerator->generate('project_management.presentation.show', ['projectUuid' => $item->uuid]),
                $this->urlGenerator->generate('project_management.presentation.edit', ['projectUuid' => $item->uuid])
            ),
            $result->items
        );

        $categoryOptions = array_map(
            static fn ($category): string => $category->getName(),
            $this->projectManagementDomainService->getAllProjectCategories()
        );

        return new ProjectListViewDto(
            $items,
            (string) ($searchQuery ?? ''),
            (string) ($categoryFilter ?? ''),
            $categoryOptions,
            (string) ($sortBy ?? 'updatedAt'),
            (string) ($sortDirection ?? 'DESC'),
            $this->urlGenerator->generate('project_management.presentation.create'),
            $this->urlGenerator->generate('track_management.presentation.index')
        );
    }
}
