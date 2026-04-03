<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\SymfonyEvent;

use DateTimeImmutable;

readonly class ProjectUpdatedSymfonyEvent
{
    /**
     * @param list<string> $details
     */
    public function __construct(
        public string            $projectUuid,
        public array             $details,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
