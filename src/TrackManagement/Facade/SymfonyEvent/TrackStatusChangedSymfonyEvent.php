<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\SymfonyEvent;

use App\TrackManagement\Domain\Enum\TrackStatus;
use DateTimeImmutable;

readonly class TrackStatusChangedSymfonyEvent
{
    public function __construct(
        public string            $trackUuid,
        public TrackStatus       $fromStatus,
        public TrackStatus       $toStatus,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
