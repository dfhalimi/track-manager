<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Service;

use App\TrackManagement\Domain\Dto\TrackNamingInputDto;
use App\TrackManagement\Domain\Service\TrackManagementDomainServiceInterface;
use App\TrackManagement\Domain\Service\TrackNamingDomainServiceInterface;
use App\TrackManagement\Domain\Support\MusicalKeyCatalog;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use App\TrackManagement\Presentation\Dto\TrackFormViewDto;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class TrackFormPresentationService implements TrackFormPresentationServiceInterface
{
    public function __construct(
        private TrackManagementDomainServiceInterface $trackManagementDomainService,
        private TrackManagementFacadeInterface        $trackManagementFacade,
        private TrackNamingDomainServiceInterface     $trackNamingDomainService,
        private UrlGeneratorInterface                 $urlGenerator
    ) {
    }

    /**
     * @param list<float>|null  $bpms
     * @param list<string>|null $musicalKeys
     */
    public function buildCreateFormViewDto(
        ?string $beatName = null,
        ?string $title = null,
        ?string $publishingName = null,
        ?array  $bpms = null,
        ?array  $musicalKeys = null,
        ?string $notes = null,
        ?string $isrc = null
    ): TrackFormViewDto {
        $trackNumber    = $this->trackManagementDomainService->getNextTrackNumberPreview();
        $beatName       = (string) ($beatName ?? '');
        $bpms           = $bpms ?? [120.0];
        $musicalKeys    = $this->normalizeMusicalKeys($musicalKeys ?? []);
        $suggestedTitle = $this->trackNamingDomainService->buildSuggestedTitle(
            new TrackNamingInputDto($trackNumber, $beatName, $bpms, $musicalKeys)
        );

        return new TrackFormViewDto(
            null,
            $trackNumber,
            $beatName,
            (string) ($title ?? $suggestedTitle),
            $publishingName,
            $bpms,
            $musicalKeys,
            MusicalKeyCatalog::all(),
            $notes,
            $isrc,
            $this->urlGenerator->generate('track_management.presentation.create'),
            $this->urlGenerator->generate('track_management.presentation.index'),
            'Track erstellen',
            $suggestedTitle,
            false
        );
    }

    /**
     * @param list<float>|null  $bpms
     * @param list<string>|null $musicalKeys
     */
    public function buildEditFormViewDto(
        string  $trackUuid,
        ?string $beatName = null,
        ?string $title = null,
        ?string $publishingName = null,
        ?array  $bpms = null,
        ?array  $musicalKeys = null,
        ?string $notes = null,
        ?string $isrc = null
    ): TrackFormViewDto {
        $track                = $this->trackManagementFacade->getTrackByUuid($trackUuid);
        $effectiveBeatName    = (string) ($beatName ?? $track->beatName);
        $effectiveBpms        = $bpms ?? $track->bpms;
        $effectiveMusicalKeys = $this->normalizeMusicalKeys($musicalKeys ?? $track->musicalKeys);
        $suggestedTitle       = $this->trackNamingDomainService->buildUpdatedTitleSuggestion(
            new TrackNamingInputDto($track->trackNumber, $effectiveBeatName, $effectiveBpms, $effectiveMusicalKeys)
        );

        return new TrackFormViewDto(
            $track->uuid,
            $track->trackNumber,
            $effectiveBeatName,
            (string) ($title ?? $track->title),
            $publishingName ?? $track->publishingName,
            $effectiveBpms,
            $effectiveMusicalKeys,
            MusicalKeyCatalog::all(),
            $notes ?? $track->notes,
            $isrc  ?? $track->isrc,
            $this->urlGenerator->generate('track_management.presentation.edit', ['trackUuid' => $trackUuid]),
            $this->urlGenerator->generate('track_management.presentation.show', ['trackUuid' => $trackUuid]),
            'Track speichern',
            $suggestedTitle,
            true
        );
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
}
