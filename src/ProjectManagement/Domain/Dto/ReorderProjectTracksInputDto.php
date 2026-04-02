<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Dto;

readonly class ReorderProjectTracksInputDto
{
    /**
     * @param list<string> $orderedTrackUuids
     */
    public function __construct(
        public string $projectUuid,
        public array  $orderedTrackUuids
    ) {
    }
}
