<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Dto;

readonly class ToggleChecklistItemInputDto
{
    public function __construct(
        public string $trackUuid,
        public string $itemUuid,
        public bool   $isCompleted
    ) {
    }
}
