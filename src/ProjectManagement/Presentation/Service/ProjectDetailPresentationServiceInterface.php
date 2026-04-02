<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\ProjectManagement\Presentation\Dto\ProjectDetailViewDto;
use App\ProjectManagement\Presentation\Dto\ProjectTrackOptionViewDto;

interface ProjectDetailPresentationServiceInterface
{
    public function buildProjectDetailViewDto(string $projectUuid): ProjectDetailViewDto;

    /**
     * @return list<ProjectTrackOptionViewDto>
     */
    public function buildAvailableTrackSuggestions(string $projectUuid, ?string $query, int $limit): array;
}
