<?php

declare(strict_types=1);

namespace App\ActivityHistory\Domain\Dto;

use DateTimeImmutable;

readonly class RecordActivityHistoryEntryInputDto
{
    /**
     * @param list<string> $details
     */
    public function __construct(
        public string            $entityType,
        public string            $entityUuid,
        public string            $eventType,
        public string            $summary,
        public array             $details,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
