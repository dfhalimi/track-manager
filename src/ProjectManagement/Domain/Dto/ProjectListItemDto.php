<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Dto;

use DateTimeImmutable;

readonly class ProjectListItemDto
{
    public function __construct(
        public string $uuid,
        public string $title,
        public string $categoryName,
        public int $trackCount,
        public DateTimeImmutable $updatedAt
    ) {
    }
}
