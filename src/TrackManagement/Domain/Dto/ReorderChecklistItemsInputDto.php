<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Dto;

readonly class ReorderChecklistItemsInputDto
{
    /**
     * @param list<string> $orderedItemUuids
     */
    public function __construct(
        public string $trackUuid,
        public array $orderedItemUuids
    ) {
    }
}
