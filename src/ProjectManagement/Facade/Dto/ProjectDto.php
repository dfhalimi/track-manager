<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\Dto;

use DateTimeImmutable;

readonly class ProjectDto
{
    public function __construct(
        public string            $uuid,
        public string            $title,
        public string            $categoryUuid,
        public string            $categoryName,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt
    ) {
    }
}
