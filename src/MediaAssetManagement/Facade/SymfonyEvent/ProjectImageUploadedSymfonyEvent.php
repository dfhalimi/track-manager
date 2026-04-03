<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Facade\SymfonyEvent;

use DateTimeImmutable;

readonly class ProjectImageUploadedSymfonyEvent
{
    public function __construct(
        public string            $projectUuid,
        public string            $originalFilename,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
