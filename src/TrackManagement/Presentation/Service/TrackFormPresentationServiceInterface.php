<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Service;

use App\TrackManagement\Presentation\Dto\TrackFormViewDto;

interface TrackFormPresentationServiceInterface
{
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
    ): TrackFormViewDto;

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
    ): TrackFormViewDto;
}
