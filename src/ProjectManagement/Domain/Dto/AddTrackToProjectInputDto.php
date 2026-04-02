<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Dto;

readonly class AddTrackToProjectInputDto
{
    public function __construct(
        public string $projectUuid,
        public string $trackUuid
    ) {
    }
}
