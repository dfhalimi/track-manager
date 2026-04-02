<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\Dto;

readonly class ProjectTrackAssignmentDto
{
    public function __construct(
        public string $trackUuid,
        public int $position
    ) {
    }
}
