<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\Dto;

readonly class ProjectListExportItemDto
{
    /**
     * @param list<string> $artists
     */
    public function __construct(
        public string $uuid,
        public string $title,
        public string $categoryName,
        public array  $artists,
        public bool   $cancelled,
        public bool   $published,
        public int    $trackCount
    ) {
    }
}
