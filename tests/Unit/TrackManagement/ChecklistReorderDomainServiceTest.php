<?php

declare(strict_types=1);

use App\TrackManagement\Domain\Dto\ReorderChecklistItemsInputDto;
use App\TrackManagement\Domain\Entity\ChecklistItem;
use App\TrackManagement\Domain\Service\ChecklistDomainService;
use App\TrackManagement\Infrastructure\Repository\ChecklistItemRepositoryInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;

describe('Checklist reordering', function (): void {
    it('reassigns checklist item positions in the submitted order', function (): void {
        $first  = createChecklistItem('track-1', 'item-1', 1);
        $second = createChecklistItem('track-1', 'item-2', 2);
        $third  = createChecklistItem('track-1', 'item-3', 3);

        $repository = new InMemoryChecklistItemRepository([$first, $second, $third]);
        $service    = new ChecklistDomainService($repository);

        $service->reorderChecklistItems(
            new ReorderChecklistItemsInputDto('track-1', ['item-3', 'item-1', 'item-2'])
        );

        $items = $repository->findByTrackUuid('track-1');

        expect(array_map(static fn (ChecklistItem $item): string => $item->getUuid(), $items))
            ->toBe(['item-3', 'item-1', 'item-2']);
        expect(array_map(static fn (ChecklistItem $item): int => $item->getPosition(), $items))
            ->toBe([1, 2, 3]);
    });

    it('rejects duplicate checklist item uuids', function (): void {
        $repository = new InMemoryChecklistItemRepository([
            createChecklistItem('track-1', 'item-1', 1),
            createChecklistItem('track-1', 'item-2', 2),
            createChecklistItem('track-1', 'item-3', 3),
        ]);
        $service = new ChecklistDomainService($repository);

        $action = static fn () => $service->reorderChecklistItems(
            new ReorderChecklistItemsInputDto('track-1', ['item-1', 'item-1', 'item-3'])
        );

        expect($action)->toThrow(ValueError::class, 'Checklist reorder must not contain duplicate item UUIDs.');
    });

    it('rejects incomplete checklist item lists', function (): void {
        $repository = new InMemoryChecklistItemRepository([
            createChecklistItem('track-1', 'item-1', 1),
            createChecklistItem('track-1', 'item-2', 2),
            createChecklistItem('track-1', 'item-3', 3),
        ]);
        $service = new ChecklistDomainService($repository);

        $action = static fn () => $service->reorderChecklistItems(
            new ReorderChecklistItemsInputDto('track-1', ['item-1', 'item-2'])
        );

        expect($action)->toThrow(ValueError::class, 'Checklist reorder must include all items exactly once.');
    });

    it('rejects foreign checklist item uuids', function (): void {
        $repository = new InMemoryChecklistItemRepository([
            createChecklistItem('track-1', 'item-1', 1),
            createChecklistItem('track-1', 'item-2', 2),
            createChecklistItem('track-1', 'item-3', 3),
        ]);
        $service = new ChecklistDomainService($repository);

        $action = static fn () => $service->reorderChecklistItems(
            new ReorderChecklistItemsInputDto('track-1', ['item-1', 'item-2', 'item-999'])
        );

        expect($action)->toThrow(ValueError::class, 'Checklist reorder contains an unknown item UUID.');
    });
});

function createChecklistItem(string $trackUuid, string $itemUuid, int $position): ChecklistItem
{
    $item = new ChecklistItem();
    $item->setUuid($itemUuid);
    $item->setTrackUuid($trackUuid);
    $item->setLabel('Label ' . $itemUuid);
    $item->setPosition($position);
    $item->setIsCompleted(false);
    $item->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
    $item->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

    return $item;
}

final class InMemoryChecklistItemRepository implements ChecklistItemRepositoryInterface
{
    /**
     * @var array<string, ChecklistItem>
     */
    private array $itemsByUuid = [];

    /**
     * @param list<ChecklistItem> $items
     */
    public function __construct(array $items)
    {
        foreach ($items as $item) {
            $this->itemsByUuid[$item->getUuid()] = $item;
        }
    }

    public function save(ChecklistItem $item): void
    {
        $this->itemsByUuid[$item->getUuid()] = $item;
    }

    public function saveMany(array $items): void
    {
        foreach ($items as $item) {
            $this->save($item);
        }
    }

    public function remove(ChecklistItem $item): void
    {
        unset($this->itemsByUuid[$item->getUuid()]);
    }

    public function findByUuid(string $itemUuid): ?ChecklistItem
    {
        return $this->itemsByUuid[$itemUuid] ?? null;
    }

    public function findByTrackUuid(string $trackUuid): array
    {
        $items = array_values(
            array_filter(
                $this->itemsByUuid,
                static fn (ChecklistItem $item): bool => $item->getTrackUuid() === $trackUuid
            )
        );

        usort(
            $items,
            static fn (ChecklistItem $left, ChecklistItem $right): int => $left->getPosition() <=> $right->getPosition()
        );

        return $items;
    }

    public function countByTrackUuid(string $trackUuid): int
    {
        return count($this->findByTrackUuid($trackUuid));
    }

    public function getNextPositionForTrack(string $trackUuid): int
    {
        return $this->countByTrackUuid($trackUuid) + 1;
    }

    public function removeAllByTrackUuid(string $trackUuid): void
    {
        foreach ($this->findByTrackUuid($trackUuid) as $item) {
            unset($this->itemsByUuid[$item->getUuid()]);
        }
    }
}
