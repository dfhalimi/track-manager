<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\TrackManagement\Domain\Entity\ChecklistItem;
use App\TrackManagement\Domain\Enum\TrackStatus;

readonly class TrackStatusResolver implements TrackStatusResolverInterface
{
    public function resolveStatus(array $checklistItems): TrackStatus
    {
        if ($checklistItems === []) {
            return TrackStatus::New;
        }

        $completedCount = count(
            array_filter(
                $checklistItems,
                static fn (object $item): bool => $item instanceof ChecklistItem && $item->isCompleted()
            )
        );

        if ($completedCount === 0) {
            return TrackStatus::New;
        }

        if ($completedCount === count($checklistItems)) {
            return TrackStatus::Done;
        }

        return TrackStatus::InProgress;
    }
}
