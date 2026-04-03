<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\ProjectManagement\Infrastructure\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Infrastructure\Repository\ProjectTrackAssignmentRepositoryInterface;
use App\TrackManagement\Domain\Dto\CreateTrackInputDto;
use App\TrackManagement\Domain\Dto\TrackListFilterDto;
use App\TrackManagement\Domain\Dto\TrackListItemDto;
use App\TrackManagement\Domain\Dto\TrackListResultDto;
use App\TrackManagement\Domain\Dto\TrackNamingInputDto;
use App\TrackManagement\Domain\Dto\UpdateTrackInputDto;
use App\TrackManagement\Domain\Entity\Track;
use App\TrackManagement\Domain\Support\MusicalKeyCatalog;
use App\TrackManagement\Facade\SymfonyEvent\TrackCancelledSymfonyEvent;
use App\TrackManagement\Facade\SymfonyEvent\TrackCreatedSymfonyEvent;
use App\TrackManagement\Facade\SymfonyEvent\TrackReactivatedSymfonyEvent;
use App\TrackManagement\Facade\SymfonyEvent\TrackUpdatedSymfonyEvent;
use App\TrackManagement\Infrastructure\Repository\TrackRepositoryInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use ValueError;

use function abs;

readonly class TrackManagementDomainService implements TrackManagementDomainServiceInterface
{
    public function __construct(
        private TrackRepositoryInterface                  $trackRepository,
        private TrackNamingDomainServiceInterface         $trackNamingDomainService,
        private ChecklistDomainServiceInterface           $checklistDomainService,
        private ProgressCalculatorInterface               $progressCalculator,
        private TrackStatusResolverInterface              $trackStatusResolver,
        private ProjectTrackAssignmentRepositoryInterface $projectTrackAssignmentRepository,
        private ProjectRepositoryInterface                $projectRepository,
        private EventDispatcherInterface                  $eventDispatcher
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
        $track->setCancelled(false);
        $track->setCreatedAt($now);
        $track->setUpdatedAt($now);

        $this->validateTrack($track);

        $this->trackRepository->save($track);
        $this->checklistDomainService->createDefaultChecklistForTrack($track->getUuid());
        $this->eventDispatcher->dispatch(
            new TrackCreatedSymfonyEvent(
                $track->getUuid(),
                [
                    sprintf('Beat Name: %s', $track->getBeatName()),
                    sprintf('Title: %s', $track->getTitle()),
                    sprintf('BPM: %s', $this->formatBpms($track->getBpms())),
                    sprintf('Key: %s', $this->formatMusicalKeys($track->getMusicalKeys())),
                ],
                $now
            )
        );

        return $track;
    }

    public function updateTrack(UpdateTrackInputDto $input): Track
    {
        $track = $this->trackRepository->getByUuid($input->trackUuid);
        $this->assertTrackIsActive($track);
        $before = $this->captureTrackSnapshot($track);

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
        $changes = $this->buildTrackChangeDetails($before, $track);
        if ($changes !== []) {
            $this->eventDispatcher->dispatch(
                new TrackUpdatedSymfonyEvent(
                    $track->getUuid(),
                    $changes,
                    $track->getUpdatedAt()
                )
            );
        }

        return $track;
    }

    public function deleteTrack(string $trackUuid): void
    {
        $track = $this->trackRepository->getByUuid($trackUuid);
        $this->checklistDomainService->deleteChecklistByTrackUuid($trackUuid);
        $this->trackRepository->remove($track);
    }

    public function cancelTrack(string $trackUuid): Track
    {
        $track = $this->trackRepository->getByUuid($trackUuid);
        if ($track->isCancelled()) {
            return $track;
        }

        $track->setCancelled(true);
        $track->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());
        $this->trackRepository->save($track);
        $this->eventDispatcher->dispatch(
            new TrackCancelledSymfonyEvent(
                $track->getUuid(),
                $track->getUpdatedAt()
            )
        );

        return $track;
    }

    public function reactivateTrack(string $trackUuid): Track
    {
        $track = $this->trackRepository->getByUuid($trackUuid);
        if (!$track->isCancelled()) {
            return $track;
        }

        $track->setCancelled(false);
        $track->setUpdatedAt(DateAndTimeService::getDateTimeImmutable());
        $this->trackRepository->save($track);
        $this->validateTrack($track);
        $this->eventDispatcher->dispatch(
            new TrackReactivatedSymfonyEvent(
                $track->getUuid(),
                $track->getUpdatedAt()
            )
        );

        return $track;
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
        $items       = $this->buildFilteredTrackListItems($filter);
        $totalItems  = count($items);
        $perPage     = $this->normalizePerPage($filter->perPage);
        $totalPages  = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min(max(1, $filter->page), $totalPages);
        $offset      = ($currentPage - 1) * $perPage;

        return new TrackListResultDto(
            array_slice($items, $offset, $perPage),
            $totalItems,
            $currentPage,
            $perPage,
            $totalPages
        );
    }

    public function getTrackSearchSuggestions(TrackListFilterDto $filter, int $limit): array
    {
        $searchQuery = trim((string) ($filter->searchQuery ?? ''));
        if ($searchQuery === '') {
            return [];
        }

        $suggestions = [];
        foreach ($this->buildFilteredTrackListItems($filter) as $item) {
            foreach ($this->extractTrackSuggestionCandidates($item) as $candidate) {
                if (!$this->matchesSearchQuery($candidate, $searchQuery)) {
                    continue;
                }

                $normalizedCandidate = mb_strtolower($candidate);
                if (array_key_exists($normalizedCandidate, $suggestions)) {
                    continue;
                }

                $suggestions[$normalizedCandidate] = $candidate;
                if (count($suggestions) >= $limit) {
                    break 2;
                }
            }
        }

        return array_values($suggestions);
    }

    /**
     * @return list<TrackListItemDto>
     */
    private function buildFilteredTrackListItems(TrackListFilterDto $filter): array
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
                $track->isCancelled(),
                $this->isTrackPublished($track->getUuid()),
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

        return $items;
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

            if (abs($bpm - round($bpm, 3)) >= 0.000001) {
                throw new ValueError('All BPM values must have at most 3 decimal places.');
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
     * @param list<float>  $bpms
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
     * @param list<float> $bpms
     *
     * @return list<float>
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
     * @param list<float> $bpms
     */
    private function extractPrimaryBpm(array $bpms): float
    {
        return $bpms[0] ?? 0.0;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function assertTrackIsActive(Track $track): void
    {
        if ($track->isCancelled()) {
            throw new ValueError('Archivierte Tracks koennen nicht bearbeitet werden.');
        }
    }

    private function normalizePerPage(int $perPage): int
    {
        return max(1, $perPage);
    }

    /**
     * @return list<string>
     */
    private function extractTrackSuggestionCandidates(TrackListItemDto $item): array
    {
        $candidates = [
            (string) $item->trackNumber,
            $item->beatName,
            $item->title,
        ];

        if ($item->publishingName !== null && trim($item->publishingName) !== '') {
            $candidates[] = $item->publishingName;
        }

        return $candidates;
    }

    private function matchesSearchQuery(string $candidate, string $searchQuery): bool
    {
        return mb_stripos($candidate, $searchQuery) !== false;
    }

    private function isTrackPublished(string $trackUuid): bool
    {
        foreach ($this->projectTrackAssignmentRepository->findByTrackUuid($trackUuid) as $assignment) {
            $project = $this->projectRepository->findByUuid($assignment->getProjectUuid());
            if ($project === null || $project->isCancelled()) {
                continue;
            }

            if ($project->isPublished()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{
     *     beatName: string,
     *     title: string,
     *     publishingName: ?string,
     *     bpms: string,
     *     musicalKeys: string,
     *     notes: ?string,
     *     isrc: ?string
     * }
     */
    private function captureTrackSnapshot(Track $track): array
    {
        return [
            'beatName'       => $track->getBeatName(),
            'title'          => $track->getTitle(),
            'publishingName' => $track->getPublishingName(),
            'bpms'           => $this->formatBpms($track->getBpms()),
            'musicalKeys'    => $this->formatMusicalKeys($track->getMusicalKeys()),
            'notes'          => $track->getNotes(),
            'isrc'           => $track->getIsrc(),
        ];
    }

    /**
     * @param array{
     *     beatName: string,
     *     title: string,
     *     publishingName: ?string,
     *     bpms: string,
     *     musicalKeys: string,
     *     notes: ?string,
     *     isrc: ?string
     * } $before
     *
     * @return list<string>
     */
    private function buildTrackChangeDetails(array $before, Track $track): array
    {
        $changes = [];
        $after   = $this->captureTrackSnapshot($track);

        $labels = [
            'beatName'       => 'Beat Name',
            'title'          => 'Title',
            'publishingName' => 'Publishing Name',
            'bpms'           => 'BPM',
            'musicalKeys'    => 'Key',
            'notes'          => 'Notizen',
            'isrc'           => 'ISRC',
        ];

        foreach ($labels as $field => $label) {
            if ($before[$field] === $after[$field]) {
                continue;
            }

            $changes[] = sprintf(
                '%s: %s -> %s',
                $label,
                $this->normalizeHistoryValue($before[$field]),
                $this->normalizeHistoryValue($after[$field])
            );
        }

        return $changes;
    }

    /**
     * @param list<float> $bpms
     */
    private function formatBpms(array $bpms): string
    {
        return implode(', ', array_map(fn (float $bpm): string => $this->formatBpm($bpm), $bpms));
    }

    /**
     * @param list<string> $musicalKeys
     */
    private function formatMusicalKeys(array $musicalKeys): string
    {
        return implode(', ', $musicalKeys);
    }

    private function normalizeHistoryValue(?string $value): string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? '—' : $trimmed;
    }

    private function formatBpm(float $bpm): string
    {
        $formattedBpm = number_format($bpm, 3, '.', '');
        $formattedBpm = rtrim($formattedBpm, '0');

        return rtrim($formattedBpm, '.');
    }
}
