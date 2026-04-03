<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\SymfonyEvent;

use DateTimeImmutable;

readonly class TrackAssignedToProjectSymfonyEvent
{
    public function __construct(
        public string            $projectUuid,
        public string            $trackUuid,
        public int               $position,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
