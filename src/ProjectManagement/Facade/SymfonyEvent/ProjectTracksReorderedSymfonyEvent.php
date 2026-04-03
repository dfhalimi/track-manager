<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\SymfonyEvent;

use DateTimeImmutable;

readonly class ProjectTracksReorderedSymfonyEvent
{
    /**
     * @param list<string> $orderedTrackUuids
     */
    public function __construct(
        public string            $projectUuid,
        public array             $orderedTrackUuids,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
