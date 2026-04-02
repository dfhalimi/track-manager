<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

interface ProgressCalculatorInterface
{
    /**
     * @param list<object> $checklistItems
     */
    public function calculateProgress(array $checklistItems): int;
}
