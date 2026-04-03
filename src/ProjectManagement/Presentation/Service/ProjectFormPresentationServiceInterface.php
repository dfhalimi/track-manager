<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\ProjectManagement\Presentation\Dto\ProjectFormViewDto;

interface ProjectFormPresentationServiceInterface
{
    /**
     * @param ?list<string> $artists
     */
    public function buildCreateFormViewDto(
        ?string $title = null,
        ?string $categoryName = null,
        ?array  $artists = null
    ): ProjectFormViewDto;

    /**
     * @param ?list<string> $artists
     */
    public function buildEditFormViewDto(
        string  $projectUuid,
        ?string $title = null,
        ?string $categoryName = null,
        ?array  $artists = null,
        ?string $publishedAtInputValue = null
    ): ProjectFormViewDto;
}
