<?php

declare(strict_types=1);

use App\TrackManagement\Domain\Dto\ToggleChecklistItemInputDto;
use App\TrackManagement\Domain\Entity\ChecklistItem;
use App\TrackManagement\Domain\Enum\TrackStatus;
use App\TrackManagement\Domain\Service\ChecklistDomainService;
use App\TrackManagement\Domain\Service\TrackStatusResolver;
use App\TrackManagement\Facade\SymfonyEvent\TrackStatusChangedSymfonyEvent;
use App\TrackManagement\Infrastructure\Repository\ChecklistItemRepositoryInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Component\EventDispatcher\EventDispatcher;

describe('Checklist status change history dispatching', function (): void {
    it('dispatches a track status changed event when toggling changes the derived status', function (): void {
        $repository = new ChecklistStatusChangeInMemoryRepository([
            createChecklistStatusItem('track-1', 'item-1', 1, false),
            createChecklistStatusItem('track-1', 'item-2', 2, false),
        ]);
        $dispatcher = new EventDispatcher();
        $events     = [];

        $dispatcher->addListener(
            TrackStatusChangedSymfonyEvent::class,
            static function (TrackStatusChangedSymfonyEvent $event) use (&$events): void {
                $events[] = $event;
            }
        );

        $service = new ChecklistDomainService($repository, new TrackStatusResolver(), $dispatcher);

        $service->toggleChecklistItem(new ToggleChecklistItemInputDto('track-1', 'item-1', true));

        expect($events)->toHaveCount(1);
        expect($events[0]->fromStatus)->toBe(TrackStatus::New);
        expect($events[0]->toStatus)->toBe(TrackStatus::InProgress);
    });

    it('does not dispatch an event when the derived status remains unchanged', function (): void {
        $repository = new ChecklistStatusChangeInMemoryRepository([
            createChecklistStatusItem('track-1', 'item-1', 1, false),
            createChecklistStatusItem('track-1', 'item-2', 2, false),
        ]);
        $dispatcher = new EventDispatcher();
        $events     = [];

        $dispatcher->addListener(
            TrackStatusChangedSymfonyEvent::class,
            static function (TrackStatusChangedSymfonyEvent $event) use (&$events): void {
                $events[] = $event;
            }
        );

        $service = new ChecklistDomainService($repository, new TrackStatusResolver(), $dispatcher);

        $service->toggleChecklistItem(new ToggleChecklistItemInputDto('track-1', 'item-1', false));

        expect($events)->toBeEmpty();
    });
});

function createChecklistStatusItem(string $trackUuid, string $itemUuid, int $position, bool $isCompleted): ChecklistItem
{
    $item = new ChecklistItem();
    $item->setUuid($itemUuid);
    $item->setTrackUuid($trackUuid);
    $item->setLabel('Label ' . $itemUuid);
    $item->setPosition($position);
    $item->setIsCompleted($isCompleted);
    $item->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
    $item->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

    return $item;
}

final class ChecklistStatusChangeInMemoryRepository implements ChecklistItemRepositoryInterface
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
