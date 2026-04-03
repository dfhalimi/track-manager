<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\Common\Presentation\Dto\PaginationLinkViewDto;
use App\ProjectManagement\Domain\Dto\ProjectListFilterDto;
use App\ProjectManagement\Domain\Dto\ProjectListItemDto;
use App\ProjectManagement\Domain\Entity\ProjectCategory;
use App\ProjectManagement\Domain\Service\ProjectManagementDomainServiceInterface;
use App\ProjectManagement\Presentation\Dto\ProjectListItemViewDto;
use App\ProjectManagement\Presentation\Dto\ProjectListViewDto;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class ProjectOverviewPresentationService implements ProjectOverviewPresentationServiceInterface
{
    /**
     * @var list<int>
     */
    private const array PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function __construct(
        private ProjectManagementDomainServiceInterface $projectManagementDomainService,
        private UrlGeneratorInterface                   $urlGenerator
    ) {
    }

    public function buildProjectListViewDto(
        ?string $searchQuery,
        ?string $categoryFilter,
        ?string $cancelledFilter,
        ?string $sortBy,
        ?string $sortDirection,
        int     $page,
        int     $perPage
    ): ProjectListViewDto {
        $filter = $this->buildFilterDto($searchQuery, $categoryFilter, $cancelledFilter, $sortBy, $sortDirection, $page, $perPage);
        $result = $this->projectManagementDomainService->getAllProjects($filter);

        $items = array_map(
            fn (ProjectListItemDto $item): ProjectListItemViewDto => new ProjectListItemViewDto(
                $item->uuid,
                $item->title,
                $item->categoryName,
                $item->artists,
                $item->cancelled,
                $item->published,
                $item->trackCount,
                $this->urlGenerator->generate('project_management.presentation.show', ['projectUuid' => $item->uuid]),
                $this->urlGenerator->generate('project_management.presentation.edit', ['projectUuid' => $item->uuid])
            ),
            $result->items
        );

        $categoryOptions = array_map(
            static fn (ProjectCategory $category): string => $category->getName(),
            $this->projectManagementDomainService->getAllProjectCategories()
        );

        return new ProjectListViewDto(
            $items,
            (string) ($filter->searchQuery ?? ''),
            (string) ($filter->categoryFilter ?? ''),
            (string) ($filter->cancelledFilter ?? ''),
            $categoryOptions,
            (string) ($filter->sortBy ?? 'updatedAt'),
            (string) ($filter->sortDirection ?? 'DESC'),
            $result->currentPage,
            $result->perPage,
            self::PER_PAGE_OPTIONS,
            $result->totalItems,
            $result->totalPages,
            $result->currentPage > 1 ? $this->buildIndexUrl($filter, $result->currentPage - 1) : null,
            $result->currentPage < $result->totalPages ? $this->buildIndexUrl($filter, $result->currentPage + 1) : null,
            $this->buildPageLinks($filter, $result->currentPage, $result->totalPages),
            $this->urlGenerator->generate('project_management.presentation.index'),
            $this->urlGenerator->generate('project_management.presentation.list'),
            $this->urlGenerator->generate('project_management.presentation.suggestions'),
            $this->urlGenerator->generate('project_management.presentation.create'),
            $this->urlGenerator->generate('track_management.presentation.index')
        );
    }

    public function buildProjectSearchSuggestions(
        ?string $searchQuery,
        ?string $categoryFilter,
        ?string $cancelledFilter,
        ?string $sortBy,
        ?string $sortDirection,
        int     $limit
    ): array {
        $filter = $this->buildFilterDto($searchQuery, $categoryFilter, $cancelledFilter, $sortBy, $sortDirection, 1, max(self::PER_PAGE_OPTIONS));

        return $this->projectManagementDomainService->getProjectSearchSuggestions($filter, $limit);
    }

    private function buildFilterDto(
        ?string $searchQuery,
        ?string $categoryFilter,
        ?string $cancelledFilter,
        ?string $sortBy,
        ?string $sortDirection,
        int     $page,
        int     $perPage
    ): ProjectListFilterDto {
        return new ProjectListFilterDto(
            $searchQuery,
            $categoryFilter,
            $cancelledFilter,
            $sortBy        ?? 'updatedAt',
            $sortDirection ?? 'DESC',
            max(1, $page),
            $this->normalizePerPage($perPage)
        );
    }

    /**
     * @return list<PaginationLinkViewDto>
     */
    private function buildPageLinks(ProjectListFilterDto $filter, int $currentPage, int $totalPages): array
    {
        $pageLinks = [];
        $startPage = max(1, $currentPage - 2);
        $endPage   = min($totalPages, $currentPage + 2);

        for ($page = $startPage; $page <= $endPage; ++$page) {
            $pageLinks[] = new PaginationLinkViewDto(
                $page,
                $this->buildIndexUrl($filter, $page),
                $page === $currentPage
            );
        }

        return $pageLinks;
    }

    private function buildIndexUrl(ProjectListFilterDto $filter, int $page): string
    {
        return $this->urlGenerator->generate('project_management.presentation.index', [
            'q'             => $filter->searchQuery,
            'category'      => $filter->categoryFilter,
            'cancelled'     => $filter->cancelledFilter,
            'sortBy'        => $filter->sortBy,
            'sortDirection' => $filter->sortDirection,
            'page'          => $page,
            'perPage'       => $filter->perPage,
        ]);
    }

    private function normalizePerPage(int $perPage): int
    {
        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 25;
    }
}
