<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\SymfonyEvent;

use DateTimeImmutable;

readonly class TrackUpdatedSymfonyEvent
{
    /**
     * @param list<string> $details
     */
    public function __construct(
        public string            $trackUuid,
        public array             $details,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
