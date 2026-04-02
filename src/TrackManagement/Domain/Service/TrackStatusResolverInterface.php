<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\TrackManagement\Domain\Enum\TrackStatus;

interface TrackStatusResolverInterface
{
    /**
     * @param list<object> $checklistItems
     */
    public function resolveStatus(array $checklistItems): TrackStatus;
}
