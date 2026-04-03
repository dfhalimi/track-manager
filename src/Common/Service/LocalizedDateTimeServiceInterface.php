<?php

declare(strict_types=1);

namespace App\Common\Service;

use DateTimeImmutable;

interface LocalizedDateTimeServiceInterface
{
    public function formatForDisplay(DateTimeImmutable $value): string;

    public function formatForInput(?DateTimeImmutable $value): string;

    public function parseInputToUtc(string $value): DateTimeImmutable;
}
