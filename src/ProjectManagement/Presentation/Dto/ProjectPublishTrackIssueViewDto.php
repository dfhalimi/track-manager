<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

readonly class ProjectPublishTrackIssueViewDto
{
    /**
     * @param list<string> $missingRequirements
     */
    public function __construct(
        public string $trackUuid,
        public string $label,
        public array  $missingRequirements
    ) {
    }
}
