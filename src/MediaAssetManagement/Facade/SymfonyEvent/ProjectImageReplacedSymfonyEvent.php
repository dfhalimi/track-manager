<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Facade\SymfonyEvent;

use DateTimeImmutable;

readonly class ProjectImageReplacedSymfonyEvent
{
    public function __construct(
        public string            $projectUuid,
        public string            $originalFilename,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
