<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Dto;

readonly class UpdateProjectInputDto
{
    /**
     * @param list<string> $artists
     */
    public function __construct(
        public string $projectUuid,
        public string $title,
        public string $categoryName,
        public array  $artists = []
    ) {
    }
}
