<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\TrackManagement\Domain\Dto\TrackNamingInputDto;
use App\TrackManagement\Domain\Support\MusicalKeyCatalog;
use function abs;
use function array_filter;
use function array_map;
use function array_values;
use function implode;
use function number_format;
use function preg_replace;
use function rtrim;
use function trim;

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
                static fn (float $bpm): bool => $bpm > 0 && abs($bpm - round($bpm, 3)) < 0.000001
            )
        );

        if ($normalizedBpms === []) {
            return 'UnknownBpm';
        }

        return implode(
            '__',
            array_map(
                fn (float $bpm): string => $this->formatBpmForTitle($bpm),
                $normalizedBpms
            )
        );
    }

    private function formatBpmForTitle(float $bpm): string
    {
        return str_replace('.', '_', $this->formatBpm($bpm)) . 'BPM';
    }

    private function formatBpm(float $bpm): string
    {
        $formattedBpm = number_format($bpm, 3, '.', '');
        $formattedBpm = rtrim($formattedBpm, '0');

        return rtrim($formattedBpm, '.');
    }
}
