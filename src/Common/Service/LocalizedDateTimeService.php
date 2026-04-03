<?php

declare(strict_types=1);

namespace App\Common\Service;

use DateTimeImmutable;
use DateTimeZone;
use ValueError;

readonly class LocalizedDateTimeService
{
    private DateTimeZone $displayTimezone;
    private DateTimeZone $storageTimezone;

    public function __construct(string $appTimezone)
    {
        $this->displayTimezone = new DateTimeZone($appTimezone);
        $this->storageTimezone = new DateTimeZone('UTC');
    }

    public function formatForDisplay(DateTimeImmutable $value): string
    {
        return $this->toDisplayTimezone($value)->format('d.m.Y H:i');
    }

    public function formatForInput(?DateTimeImmutable $value): string
    {
        if (!$value instanceof DateTimeImmutable) {
            return '';
        }

        return $this->toDisplayTimezone($value)->format('Y-m-d\TH:i');
    }

    public function parseInputToUtc(string $value): DateTimeImmutable
    {
        $parsedValue = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, $this->displayTimezone);
        if (!$parsedValue instanceof DateTimeImmutable) {
            throw new ValueError('Bitte gib ein gültiges Datum mit Uhrzeit an.');
        }

        return $parsedValue->setTimezone($this->storageTimezone);
    }

    private function toDisplayTimezone(DateTimeImmutable $value): DateTimeImmutable
    {
        return $value->setTimezone($this->displayTimezone);
    }
}
