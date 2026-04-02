<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Enum;

enum TrackStatus: string
{
    case New        = 'new';
    case InProgress = 'in_progress';
    case Done       = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::New        => 'New',
            self::InProgress => 'In Progress',
            self::Done       => 'Done',
        };
    }
}
