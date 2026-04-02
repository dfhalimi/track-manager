<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\TrackManagement\Domain\Entity\ChecklistItem;

readonly class ProgressCalculator implements ProgressCalculatorInterface
{
    public function calculateProgress(array $checklistItems): int
    {
        if ($checklistItems === []) {
            return 0;
        }

        $completedCount = count(
            array_filter(
                $checklistItems,
                static fn (object $item): bool => $item instanceof ChecklistItem && $item->isCompleted()
            )
        );

        return (int) round(($completedCount / count($checklistItems)) * 100);
    }
}
