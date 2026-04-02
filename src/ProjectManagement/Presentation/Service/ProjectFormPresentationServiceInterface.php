<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\ProjectManagement\Presentation\Dto\ProjectFormViewDto;

interface ProjectFormPresentationServiceInterface
{
    public function buildCreateFormViewDto(
        ?string $title = null,
        ?string $categoryName = null
    ): ProjectFormViewDto;

    public function buildEditFormViewDto(
        string $projectUuid,
        ?string $title = null,
        ?string $categoryName = null
    ): ProjectFormViewDto;
}
