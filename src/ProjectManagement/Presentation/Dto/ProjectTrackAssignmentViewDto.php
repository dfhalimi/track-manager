<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

readonly class ProjectTrackAssignmentViewDto
{
    public function __construct(
        public string $trackUuid,
        public string $label,
        public int $position,
        public string $showUrl,
        public string $removeUrl
    ) {
    }
}
