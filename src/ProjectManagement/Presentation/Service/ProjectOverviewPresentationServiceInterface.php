<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\ProjectManagement\Presentation\Dto\ProjectListViewDto;

interface ProjectOverviewPresentationServiceInterface
{
    public function buildProjectListViewDto(
        ?string $searchQuery,
        ?string $categoryFilter,
        ?string $sortBy,
        ?string $sortDirection
    ): ProjectListViewDto;
}
