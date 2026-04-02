<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\Dto;

readonly class ProjectCategoryDto
{
    public function __construct(
        public string $uuid,
        public string $name
    ) {
    }
}
