<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Dto;

readonly class CreateProjectInputDto
{
    public function __construct(
        public string $title,
        public string $categoryName
    ) {
    }
}
