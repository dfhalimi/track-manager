<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Dto;

readonly class ChecklistItemViewDto
{
    public function __construct(
        public string $uuid,
        public string $label,
        public bool   $isCompleted,
        public int    $position,
        public string $toggleUrl,
        public string $renameUrl,
        public string $removeUrl
    ) {
    }
}
