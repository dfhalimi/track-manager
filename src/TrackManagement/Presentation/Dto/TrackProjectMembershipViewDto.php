<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Dto;

readonly class TrackProjectMembershipViewDto
{
    public function __construct(
        public string $projectTitle,
        public string $categoryName,
        public int    $position,
        public string $showUrl
    ) {
    }
}
