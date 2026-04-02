<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\ProjectManagement\Presentation\Dto\ProjectDetailViewDto;

interface ProjectDetailPresentationServiceInterface
{
    public function buildProjectDetailViewDto(string $projectUuid): ProjectDetailViewDto;
}
