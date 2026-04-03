<?php

declare(strict_types=1);

namespace App\ActivityHistory\Presentation\Dto;

readonly class ActivityHistoryEntryViewDto
{
    /**
     * @param list<string> $details
     */
    public function __construct(
        public string $summary,
        public array  $details,
        public string $occurredAtLabel,
        public bool   $isSynthetic
    ) {
    }
}
