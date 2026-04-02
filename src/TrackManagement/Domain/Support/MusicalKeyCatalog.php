<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Support;

final class MusicalKeyCatalog
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $keys = [];

        foreach (self::roots() as $root) {
            $keys[] = $root . 'maj';
            $keys[] = $root . 'min';
        }

        return $keys;
    }

    public static function canonicalize(string $value): ?string
    {
        $normalized = trim($value);
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;
        $normalized = str_ireplace(['minor', 'mol'], 'min', $normalized);
        $normalized = str_ireplace(['major', 'dur'], 'maj', $normalized);

        if ($normalized === '') {
            return null;
        }

        foreach (self::all() as $option) {
            if (mb_strtolower($option) === mb_strtolower($normalized)) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function roots(): array
    {
        return ['A', 'A#', 'Bb', 'B', 'C', 'C#', 'Db', 'D', 'D#', 'Eb', 'E', 'F', 'F#', 'Gb', 'G', 'G#', 'Ab'];
    }
}
