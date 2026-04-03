<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Dto;

readonly class TrackProjectBadgeViewDto
{
    public function __construct(
        public string $title,
        public bool   $published
    ) {
    }
}
