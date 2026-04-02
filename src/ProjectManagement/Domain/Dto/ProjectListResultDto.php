<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Dto;

readonly class ProjectListResultDto
{
    /**
     * @param list<ProjectListItemDto> $items
     */
    public function __construct(
        public array $items
    ) {
    }
}
