<?php

declare(strict_types=1);

namespace App\ActivityHistory\Facade\Dto;

use DateTimeImmutable;

readonly class ActivityHistoryEntryDto
{
    /**
     * @param list<string> $details
     */
    public function __construct(
        public string            $uuid,
        public string            $entityType,
        public string            $entityUuid,
        public string            $eventType,
        public string            $summary,
        public array             $details,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
