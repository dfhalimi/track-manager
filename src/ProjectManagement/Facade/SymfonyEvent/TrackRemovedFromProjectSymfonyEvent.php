<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\SymfonyEvent;

use DateTimeImmutable;

readonly class TrackRemovedFromProjectSymfonyEvent
{
    public function __construct(
        public string            $projectUuid,
        public string            $trackUuid,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
