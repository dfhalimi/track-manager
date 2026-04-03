<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Dto;

use DateTimeImmutable;

readonly class PublishProjectInputDto
{
    public function __construct(
        public string            $projectUuid,
        public DateTimeImmutable $publishedAt
    ) {
    }
}
