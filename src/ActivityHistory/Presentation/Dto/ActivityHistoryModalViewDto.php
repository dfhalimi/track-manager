<?php

declare(strict_types=1);

namespace App\ActivityHistory\Presentation\Dto;

readonly class ActivityHistoryModalViewDto
{
    /**
     * @param list<ActivityHistoryEntryViewDto> $entries
     */
    public function __construct(
        public string $title,
        public string $description,
        public array  $entries
    ) {
    }
}
