<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\TrackManagement\Domain\Dto\AddChecklistItemInputDto;
use App\TrackManagement\Domain\Dto\RemoveChecklistItemInputDto;
use App\TrackManagement\Domain\Dto\RenameChecklistItemInputDto;
use App\TrackManagement\Domain\Dto\ReorderChecklistItemsInputDto;
use App\TrackManagement\Domain\Dto\ToggleChecklistItemInputDto;
use App\TrackManagement\Domain\Entity\ChecklistItem;

interface ChecklistDomainServiceInterface
{
    public function createDefaultChecklistForTrack(string $trackUuid): void;

    public function addChecklistItem(AddChecklistItemInputDto $input): ChecklistItem;

    public function renameChecklistItem(RenameChecklistItemInputDto $input): void;

    public function toggleChecklistItem(ToggleChecklistItemInputDto $input): void;

    public function reorderChecklistItems(ReorderChecklistItemsInputDto $input): void;

    public function removeChecklistItem(RemoveChecklistItemInputDto $input): void;

    /**
     * @return list<ChecklistItem>
     */
    public function getChecklistItemsByTrackUuid(string $trackUuid): array;

    public function deleteChecklistByTrackUuid(string $trackUuid): void;
}
