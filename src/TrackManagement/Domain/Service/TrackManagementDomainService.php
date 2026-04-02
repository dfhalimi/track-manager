<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\TrackManagement\Domain\Dto\CreateTrackInputDto;
use App\TrackManagement\Domain\Dto\TrackListFilterDto;
use App\TrackManagement\Domain\Dto\TrackListItemDto;
use App\TrackManagement\Domain\Dto\TrackListResultDto;
use App\TrackManagement\Domain\Dto\TrackNamingInputDto;
use App\TrackManagement\Domain\Dto\UpdateTrackInputDto;
use App\TrackManagement\Domain\Entity\Track;
use App\TrackManagement\Domain\Support\MusicalKeyCatalog;
use App\TrackManagement\Infrastructure\Repository\TrackRepositoryInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Component\Uid\Uuid;
use ValueError;

readonly class TrackManagementDomainService implements TrackManagementDomainServiceInterface
{
    public function __construct(
        private TrackRepositoryInterface          $trackRepository,
        private TrackNamingDomainServiceInterface $trackNamingDomainService,
        private ChecklistDomainServiceInterface   $checklistDomainService,
        private ProgressCalculatorInterface       $progressCalculator,
        private TrackStatusResolverInterface      $trackStatusResolver
    ) {
    }

    public function getNextTrackNumberPreview(): int
    {
        return $this->trackRepository->getNextTrackNumber();
    }

    public function createNewTrack(CreateTrackInputDto $input): Track
    {
        $trackNumber = $this->trackRepository->getNextTrackNumber();
        $now         = DateAndTimeService::getDateTimeImmutable();

        $track = new Track();
        $track->setUuid(Uuid::v7()->toRfc4122());
        $track->setTrackNumber($trackNumber);
        $track->setBeatName(trim($input->beatName));
        $track->setBpms($this->normalizeBpms($input->bpms));
        $track->setMusicalKeys($this->normalizeMusicalKeys($input->musicalKeys));
        $track->setTitle($this->resolveTitle($trackNumber, $input->beatName, $input->bpms, $input->musicalKeys, $input->title));
        $track->setPublishingName($this->normalizeNullableString($input->publishingName));
        $track->setNotes($this->normalizeNullableString($input->notes));
        $track->setIsrc($this->normalizeNullableString($input->isrc));
        $track->setCreatedAt($now);
        $track->setUpdatedAt($now);

        $this->validateTrack($track);

        $this->trackRepository->save($track);
        $this->checklistDomainService->createDefaultChecklistForTrack($track->getUuid());

        return $track;
    }

    public function updateTrack(UpdateTrackInputDto $input): Track
    {
        $track = $this->trackRepository->getByUuid($input->trackUuid);

        $track->setBeatName(trim($input->beatName));
        $track->setBpms($this->normalizeBpms($input->bpms));
        $track->setMusicalKeys($this->normalizeMusicalKeys($input->musicalKeys));
        $track->setTitle(
            $input->replaceTitleWithSuggestion
                ? $this->trackNamingDomainService->buildUpdatedTitleSuggestion(
                    new TrackNamingInputDto(
                        $track->getTrackNumber(),
                        $input->beatName,
                        $input->bpms,
                        $input->musicalKeys
                    )
                )
                : trim($input->title)
        );
        $track->setPublishingName($this->normalizeNullableString($input->publishingName));
        $track->setNotes($this->normalizeNullableString($input->notes));
        $track->setIsrc($this->normalizeNullableString($input->isrc));
        $track->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());

        $this->validateTrack($track);

        $this->trackRepository->save($track);

        return $track;
    }

    public function deleteTrack(string $trackUuid): void
    {
        $track = $this->trackRepository->getByUuid($trackUuid);
        $this->checklistDomainService->deleteChecklistByTrackUuid($trackUuid);
        $this->trackRepository->remove($track);
    }

    public function getTrackByUuid(string $trackUuid): Track
    {
        return $this->trackRepository->getByUuid($trackUuid);
    }

    public function getTrackByTrackNumber(int $trackNumber): ?Track
    {
        return $this->trackRepository->findByTrackNumber($trackNumber);
    }

    public function getAllTracks(TrackListFilterDto $filter): TrackListResultDto
    {
        $items = [];

        foreach ($this->trackRepository->findAllByFilter($filter) as $track) {
            $checklistItems = $this->checklistDomainService->getChecklistItemsByTrackUuid($track->getUuid());
            $status         = $this->trackStatusResolver->resolveStatus($checklistItems);

            if ($filter->statusFilter !== null && $filter->statusFilter !== '') {
                if ($filter->statusFilter === 'not_finished' && $status->value === 'done') {
                    continue;
                }

                if ($filter->statusFilter !== 'not_finished' && $status->value !== $filter->statusFilter) {
                    continue;
                }
            }

            $items[] = new TrackListItemDto(
                $track->getUuid(),
                $track->getTrackNumber(),
                $track->getBeatName(),
                $track->getTitle(),
                $track->getPublishingName(),
                $track->getBpms(),
                $track->getMusicalKeys(),
                $this->progressCalculator->calculateProgress($checklistItems),
                $status->value,
                false,
                $track->getUpdatedAt()
            );
        }

        if ($filter->sortBy === 'progress') {
            usort(
                $items,
                fn (TrackListItemDto $left, TrackListItemDto $right): int => $filter->sortDirection === 'ASC'
                    ? $left->progress  <=> $right->progress
                    : $right->progress <=> $left->progress
            );
        }

        if ($filter->sortBy === 'status') {
            usort(
                $items,
                fn (TrackListItemDto $left, TrackListItemDto $right): int => $filter->sortDirection === 'ASC'
                    ? strcmp($left->status, $right->status)
                    : strcmp($right->status, $left->status)
            );
        }

        if ($filter->sortBy === 'bpm') {
            usort(
                $items,
                fn (TrackListItemDto $left, TrackListItemDto $right): int => $filter->sortDirection === 'ASC'
                    ? $this->extractPrimaryBpm($left->bpms)  <=> $this->extractPrimaryBpm($right->bpms)
                    : $this->extractPrimaryBpm($right->bpms) <=> $this->extractPrimaryBpm($left->bpms)
            );
        }

        return new TrackListResultDto($items);
    }

    private function validateTrack(Track $track): void
    {
        if (trim($track->getBeatName()) === '') {
            throw new ValueError('Beat name must not be empty.');
        }

        if (trim($track->getTitle()) === '') {
            throw new ValueError('Track title must not be empty.');
        }

        if ($track->getBpms() === []) {
            throw new ValueError('At least one BPM is required.');
        }

        foreach ($track->getBpms() as $bpm) {
            if ($bpm <= 0) {
                throw new ValueError('All BPM values must be greater than zero.');
            }
        }

        if ($track->getMusicalKeys() === []) {
            throw new ValueError('At least one musical key is required.');
        }

        foreach ($track->getMusicalKeys() as $musicalKey) {
            if (trim($musicalKey) === '') {
                throw new ValueError('Musical keys must not be empty.');
            }

            if (MusicalKeyCatalog::canonicalize($musicalKey) === null) {
                throw new ValueError('Musical keys must be selected from the supported list.');
            }
        }
    }

    /**
     * @param list<int>    $bpms
     * @param list<string> $musicalKeys
     */
    private function resolveTitle(
        int    $trackNumber,
        string $beatName,
        array  $bpms,
        array  $musicalKeys,
        string $submittedTitle
    ): string {
        $submittedTitle = trim($submittedTitle);

        if ($submittedTitle !== '') {
            return $submittedTitle;
        }

        return $this->trackNamingDomainService->buildSuggestedTitle(new TrackNamingInputDto($trackNumber, $beatName, $bpms, $musicalKeys));
    }

    /**
     * @param list<int> $bpms
     *
     * @return list<int>
     */
    private function normalizeBpms(array $bpms): array
    {
        return $bpms;
    }

    /**
     * @param list<string> $musicalKeys
     *
     * @return list<string>
     */
    private function normalizeMusicalKeys(array $musicalKeys): array
    {
        $normalizedMusicalKeys = [];

        foreach ($musicalKeys as $musicalKey) {
            $trimmed = trim($musicalKey);
            if ($trimmed === '') {
                continue;
            }

            $canonical               = MusicalKeyCatalog::canonicalize($musicalKey);
            $normalizedMusicalKeys[] = $canonical ?? $trimmed;
        }

        return $normalizedMusicalKeys;
    }

    /**
     * @param list<int> $bpms
     */
    private function extractPrimaryBpm(array $bpms): int
    {
        return $bpms[0] ?? 0;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
