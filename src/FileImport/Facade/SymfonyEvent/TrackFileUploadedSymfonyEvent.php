<?php

declare(strict_types=1);

namespace App\FileImport\Facade\SymfonyEvent;

use DateTimeImmutable;

readonly class TrackFileUploadedSymfonyEvent
{
    public function __construct(
        public string            $trackUuid,
        public string            $originalFilename,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
