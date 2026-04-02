<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Service;

use App\TrackManagement\Presentation\Dto\TrackFormViewDto;

interface TrackFormPresentationServiceInterface
{
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
    ): TrackFormViewDto;

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
    ): TrackFormViewDto;
}
