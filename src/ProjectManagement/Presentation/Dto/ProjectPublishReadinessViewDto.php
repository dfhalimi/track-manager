<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

readonly class ProjectPublishReadinessViewDto
{
    /**
     * @param list<ProjectPublishTrackIssueViewDto> $trackIssues
     */
    public function __construct(
        public bool  $requiresConfirmation,
        public bool  $hasTracks,
        public array $trackIssues
    ) {
    }
}
