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
     * @param list<int>|null $bpms
     */
    public function buildCreateFormViewDto(
        ?string $beatName = null,
        ?string $title = null,
        ?string $publishingName = null,
        ?array  $bpms = null,
        ?string $musicalKey = null,
        ?string $notes = null,
        ?string $isrc = null
    ): TrackFormViewDto {
        $trackNumber    = $this->trackManagementDomainService->getNextTrackNumberPreview();
        $beatName       = (string) ($beatName ?? '');
        $bpms           = $bpms ?? [120];
        $musicalKey     = (string) ($musicalKey ?? 'Amin');
        $musicalKey     = MusicalKeyCatalog::canonicalize($musicalKey) ?? $musicalKey;
        $suggestedTitle = $this->trackNamingDomainService->buildSuggestedTitle(
            new TrackNamingInputDto($trackNumber, $beatName, $bpms, $musicalKey)
        );

        return new TrackFormViewDto(
            null,
            $trackNumber,
            $beatName,
            (string) ($title ?? $suggestedTitle),
            $publishingName,
            $bpms,
            $musicalKey,
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
     * @param list<int>|null $bpms
     */
    public function buildEditFormViewDto(
        string  $trackUuid,
        ?string $beatName = null,
        ?string $title = null,
        ?string $publishingName = null,
        ?array  $bpms = null,
        ?string $musicalKey = null,
        ?string $notes = null,
        ?string $isrc = null
    ): TrackFormViewDto {
        $track               = $this->trackManagementFacade->getTrackByUuid($trackUuid);
        $effectiveBeatName   = (string) ($beatName ?? $track->beatName);
        $effectiveBpms       = $bpms ?? $track->bpms;
        $effectiveMusicalKey = (string) ($musicalKey ?? $track->musicalKey);
        $effectiveMusicalKey = MusicalKeyCatalog::canonicalize($effectiveMusicalKey) ?? $effectiveMusicalKey;
        $suggestedTitle      = $this->trackNamingDomainService->buildUpdatedTitleSuggestion(
            new TrackNamingInputDto($track->trackNumber, $effectiveBeatName, $effectiveBpms, $effectiveMusicalKey)
        );

        return new TrackFormViewDto(
            $track->uuid,
            $track->trackNumber,
            $effectiveBeatName,
            (string) ($title ?? $track->title),
            $publishingName ?? $track->publishingName,
            $effectiveBpms,
            $effectiveMusicalKey,
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
}
