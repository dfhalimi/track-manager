<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Dto;

readonly class AddChecklistItemInputDto
{
    public function __construct(
        public string $trackUuid,
        public string $label
    ) {
    }
}
