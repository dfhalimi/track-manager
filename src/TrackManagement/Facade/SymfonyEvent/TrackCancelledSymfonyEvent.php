<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\SymfonyEvent;

use DateTimeImmutable;

readonly class TrackCancelledSymfonyEvent
{
    public function __construct(
        public string            $trackUuid,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
