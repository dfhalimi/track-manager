<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\TrackManagement\Domain\Dto\AddChecklistItemInputDto;
use App\TrackManagement\Domain\Dto\RemoveChecklistItemInputDto;
use App\TrackManagement\Domain\Dto\RenameChecklistItemInputDto;
use App\TrackManagement\Domain\Dto\ReorderChecklistItemsInputDto;
use App\TrackManagement\Domain\Dto\ToggleChecklistItemInputDto;
use App\TrackManagement\Domain\Entity\ChecklistItem;
use App\TrackManagement\Domain\Enum\TrackStatus;
use App\TrackManagement\Facade\SymfonyEvent\TrackStatusChangedSymfonyEvent;
use App\TrackManagement\Infrastructure\Repository\ChecklistItemRepositoryInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;
use ValueError;

readonly class ChecklistDomainService implements ChecklistDomainServiceInterface
{
    public function __construct(
        private ChecklistItemRepositoryInterface $checklistItemRepository,
        private TrackStatusResolverInterface     $trackStatusResolver,
        private EventDispatcherInterface         $eventDispatcher
    ) {
    }

    public function createDefaultChecklistForTrack(string $trackUuid): void
    {
        foreach (['Idee', 'Produktion', 'Publishing'] as $position => $label) {
            $item = new ChecklistItem();
            $item->setUuid(Uuid::v7()->toRfc4122());
            $item->setTrackUuid($trackUuid);
            $item->setLabel($label);
            $item->setPosition($position + 1);
            $item->setIsCompleted(false);
            $item->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
            $item->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

            $this->checklistItemRepository->save($item);
        }
    }

    public function addChecklistItem(AddChecklistItemInputDto $input): ChecklistItem
    {
        $previousStatus = $this->resolveTrackStatus($input->trackUuid);
        $label          = trim($input->label);
        if ($label === '') {
            throw new ValueError('Checklist label must not be empty.');
        }

        $item = new ChecklistItem();
        $item->setUuid(Uuid::v7()->toRfc4122());
        $item->setTrackUuid($input->trackUuid);
        $item->setLabel($label);
        $item->setPosition($this->checklistItemRepository->getNextPositionForTrack($input->trackUuid));
        $item->setIsCompleted(false);
        $item->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
        $item->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

        $this->checklistItemRepository->save($item);
        $this->dispatchTrackStatusChangedIfNeeded($input->trackUuid, $previousStatus);

        return $item;
    }

    public function renameChecklistItem(RenameChecklistItemInputDto $input): void
    {
        $item  = $this->requireTrackItem($input->trackUuid, $input->itemUuid);
        $label = trim($input->label);

        if ($label === '') {
            throw new ValueError('Checklist label must not be empty.');
        }

        $item->setLabel($label);
        $item->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

        $this->checklistItemRepository->save($item);
    }

    public function toggleChecklistItem(ToggleChecklistItemInputDto $input): void
    {
        $previousStatus = $this->resolveTrackStatus($input->trackUuid);
        $item           = $this->requireTrackItem($input->trackUuid, $input->itemUuid);
        $item->setIsCompleted($input->isCompleted);
        $item->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

        $this->checklistItemRepository->save($item);
        $this->dispatchTrackStatusChangedIfNeeded($input->trackUuid, $previousStatus);
    }

    public function reorderChecklistItems(ReorderChecklistItemsInputDto $input): void
    {
        $items = $this->checklistItemRepository->findByTrackUuid($input->trackUuid);

        if (count($items) !== count($input->orderedItemUuids)) {
            throw new ValueError('Checklist reorder must include all items exactly once.');
        }

        $itemsByUuid = [];
        foreach ($items as $item) {
            $itemsByUuid[$item->getUuid()] = $item;
        }

        if (count($itemsByUuid) !== count($input->orderedItemUuids)) {
            throw new ValueError('Checklist reorder must not contain duplicate item UUIDs.');
        }

        $reorderedItems = [];
        foreach ($input->orderedItemUuids as $position => $itemUuid) {
            if (array_key_exists($itemUuid, $reorderedItems)) {
                throw new ValueError('Checklist reorder must not contain duplicate item UUIDs.');
            }

            $item = $itemsByUuid[$itemUuid] ?? null;
            if ($item === null) {
                throw new ValueError('Checklist reorder contains an unknown item UUID.');
            }

            $item->setPosition($position + 1);
            $item->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());
            $reorderedItems[$itemUuid] = $item;
        }

        $this->checklistItemRepository->saveMany(array_values($reorderedItems));
    }

    public function removeChecklistItem(RemoveChecklistItemInputDto $input): void
    {
        $previousStatus = $this->resolveTrackStatus($input->trackUuid);
        if ($this->checklistItemRepository->countByTrackUuid($input->trackUuid) <= 1) {
            throw new ValueError('A track checklist must contain at least one item.');
        }

        $item = $this->requireTrackItem($input->trackUuid, $input->itemUuid);
        $this->checklistItemRepository->remove($item);
        $this->dispatchTrackStatusChangedIfNeeded($input->trackUuid, $previousStatus);
    }

    public function getChecklistItemsByTrackUuid(string $trackUuid): array
    {
        return $this->checklistItemRepository->findByTrackUuid($trackUuid);
    }

    public function deleteChecklistByTrackUuid(string $trackUuid): void
    {
        $this->checklistItemRepository->removeAllByTrackUuid($trackUuid);
    }

    private function requireTrackItem(string $trackUuid, string $itemUuid): ChecklistItem
    {
        $item = $this->checklistItemRepository->findByUuid($itemUuid);

        if ($item === null || $item->getTrackUuid() !== $trackUuid) {
            throw new ValueError('Checklist item was not found for the given track.');
        }

        return $item;
    }

    private function resolveTrackStatus(string $trackUuid): TrackStatus
    {
        return $this->trackStatusResolver->resolveStatus($this->checklistItemRepository->findByTrackUuid($trackUuid));
    }

    private function dispatchTrackStatusChangedIfNeeded(
        string      $trackUuid,
        TrackStatus $previousStatus
    ): void {
        $currentStatus = $this->resolveTrackStatus($trackUuid);
        if ($previousStatus === $currentStatus) {
            return;
        }

        $this->eventDispatcher->dispatch(
            new TrackStatusChangedSymfonyEvent(
                $trackUuid,
                $previousStatus,
                $currentStatus,
                DateAndTimeService::getDateTimeImmutable()
            )
        );
    }
}
