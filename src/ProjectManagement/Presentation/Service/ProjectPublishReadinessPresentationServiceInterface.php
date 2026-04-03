<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\ProjectManagement\Presentation\Dto\ProjectPublishReadinessViewDto;

interface ProjectPublishReadinessPresentationServiceInterface
{
    public function buildProjectPublishReadinessViewDto(string $projectUuid): ProjectPublishReadinessViewDto;
}
