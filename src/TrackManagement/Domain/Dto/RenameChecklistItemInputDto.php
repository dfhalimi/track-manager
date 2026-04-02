<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Dto;

readonly class RenameChecklistItemInputDto
{
    public function __construct(
        public string $trackUuid,
        public string $itemUuid,
        public string $label
    ) {
    }
}
