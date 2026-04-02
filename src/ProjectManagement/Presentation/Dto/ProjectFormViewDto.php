<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

readonly class ProjectFormViewDto
{
    /**
     * @param list<string> $artists
     * @param list<string> $categoryOptions
     */
    public function __construct(
        public ?string $projectUuid,
        public string  $title,
        public string  $categoryName,
        public array   $artists,
        public array   $categoryOptions,
        public string  $formAction,
        public string  $cancelUrl,
        public string  $submitLabel,
        public bool    $isEditMode
    ) {
    }
}
