<?php

declare(strict_types=1);

use App\TrackManagement\Domain\Entity\ChecklistItem;
use App\TrackManagement\Domain\Enum\TrackStatus;
use App\TrackManagement\Domain\Service\ProgressCalculator;
use App\TrackManagement\Domain\Service\TrackStatusResolver;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;

describe('Track checklist derivations', function (): void {
    it('derives progress and status from checklist items', function (): void {
        $progressCalculator = new ProgressCalculator();
        $statusResolver     = new TrackStatusResolver();

        $first = new ChecklistItem();
        $first->setUuid('item-1');
        $first->setTrackUuid('track-1');
        $first->setLabel('Idee');
        $first->setPosition(1);
        $first->setIsCompleted(true);
        $first->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
        $first->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

        $second = new ChecklistItem();
        $second->setUuid('item-2');
        $second->setTrackUuid('track-1');
        $second->setLabel('Produktion');
        $second->setPosition(2);
        $second->setIsCompleted(false);
        $second->setCreatedAt(DateAndTimeService::getDateTimeImmutable());
        $second->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

        expect($progressCalculator->calculateProgress([$first, $second]))->toBe(50);
        expect($statusResolver->resolveStatus([$first, $second]))->toBe(TrackStatus::InProgress);
    });
});
