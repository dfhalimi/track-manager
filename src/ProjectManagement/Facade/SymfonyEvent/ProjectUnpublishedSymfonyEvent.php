<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\SymfonyEvent;

use DateTimeImmutable;

readonly class ProjectUnpublishedSymfonyEvent
{
    public function __construct(
        public string            $projectUuid,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
