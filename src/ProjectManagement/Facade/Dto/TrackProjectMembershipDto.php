<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\Dto;

readonly class TrackProjectMembershipDto
{
    public function __construct(
        public string $projectUuid,
        public string $projectTitle,
        public string $categoryName,
        public int $position
    ) {
    }
}
