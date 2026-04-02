<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\TrackManagement\Domain\Dto\TrackNamingInputDto;

interface TrackNamingDomainServiceInterface
{
    public function buildSuggestedTitle(TrackNamingInputDto $input): string;

    public function buildUpdatedTitleSuggestion(TrackNamingInputDto $input): string;

    public function normalizeBeatName(string $beatName): string;

    public function normalizeMusicalKey(string $musicalKey): string;

    /**
     * @param list<int> $bpms
     */
    public function normalizeBpms(array $bpms): string;
}
