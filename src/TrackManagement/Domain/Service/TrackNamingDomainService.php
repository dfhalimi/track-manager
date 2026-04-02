<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\TrackManagement\Domain\Dto\TrackNamingInputDto;
use App\TrackManagement\Domain\Support\MusicalKeyCatalog;

readonly class TrackNamingDomainService implements TrackNamingDomainServiceInterface
{
    public function buildSuggestedTitle(TrackNamingInputDto $input): string
    {
        return sprintf(
            '%d_%s_%s_%s',
            $input->trackNumber,
            $this->normalizeBeatName($input->beatName),
            $this->normalizeBpms($input->bpms),
            $this->normalizeMusicalKeys($input->musicalKeys)
        );
    }

    public function buildUpdatedTitleSuggestion(TrackNamingInputDto $input): string
    {
        return $this->buildSuggestedTitle($input);
    }

    public function normalizeBeatName(string $beatName): string
    {
        $normalized = trim($beatName);
        $normalized = preg_replace('/[^A-Za-z0-9]+/', '_', $normalized) ?? $normalized;
        $normalized = trim($normalized, '_');

        return $normalized === '' ? 'UntitledBeat' : $normalized;
    }

    /**
     * @param list<string> $musicalKeys
     */
    public function normalizeMusicalKeys(array $musicalKeys): string
    {
        $normalizedMusicalKeys = array_values(
            array_filter(
                array_map(
                    static fn (string $musicalKey): ?string => MusicalKeyCatalog::canonicalize($musicalKey),
                    $musicalKeys
                ),
                static fn (?string $musicalKey): bool => $musicalKey !== null
            )
        );

        if ($normalizedMusicalKeys === []) {
            return 'UnknownKey';
        }

        return implode('_', $normalizedMusicalKeys);
    }

    public function normalizeBpms(array $bpms): string
    {
        $normalizedBpms = array_values(
            array_filter(
                $bpms,
                static fn (int $bpm): bool => $bpm > 0
            )
        );

        if ($normalizedBpms === []) {
            return 'UnknownBpm';
        }

        return implode(
            '_',
            array_map(
                static fn (int $bpm): string => sprintf('%dBPM', $bpm),
                $normalizedBpms
            )
        );
    }
}
