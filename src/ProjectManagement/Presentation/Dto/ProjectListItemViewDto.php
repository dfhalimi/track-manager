<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

readonly class ProjectListItemViewDto
{
    public function __construct(
        public string $uuid,
        public string $title,
        public string $categoryName,
        public int    $trackCount,
        public string $showUrl,
        public string $editUrl
    ) {
    }
}
