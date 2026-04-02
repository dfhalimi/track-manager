<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

readonly class ProjectTrackOptionViewDto
{
    public function __construct(
        public string $trackUuid,
        public string $label
    ) {
    }
}
