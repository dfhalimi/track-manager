<?php

declare(strict_types=1);

namespace App\TrackManagement\Infrastructure\Repository;

use App\TrackManagement\Domain\Entity\ChecklistItem;

interface ChecklistItemRepositoryInterface
{
    public function save(ChecklistItem $item): void;

    /**
     * @param list<ChecklistItem> $items
     */
    public function saveMany(array $items): void;

    public function remove(ChecklistItem $item): void;

    public function findByUuid(string $itemUuid): ?ChecklistItem;

    /**
     * @return list<ChecklistItem>
     */
    public function findByTrackUuid(string $trackUuid): array;

    public function countByTrackUuid(string $trackUuid): int;

    public function getNextPositionForTrack(string $trackUuid): int;

    public function removeAllByTrackUuid(string $trackUuid): void;
}
