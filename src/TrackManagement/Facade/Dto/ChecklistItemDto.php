<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\Dto;

readonly class ChecklistItemDto
{
    public function __construct(
        public string $uuid,
        public string $label,
        public bool   $isCompleted,
        public int    $position
    ) {
    }
}
