<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Support;

final class ProjectCategoryCatalog
{
    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return ['Single', 'EP', 'Album'];
    }

    public static function normalizeDisplayName(string $value): string
    {
        $collapsed  = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
        $normalized = self::normalizeStorageValue($collapsed);

        return match ($normalized) {
            'single' => 'Single',
            'ep'     => 'EP',
            'album'  => 'Album',
            default  => $collapsed,
        };
    }

    public static function normalizeStorageValue(string $value): string
    {
        $collapsed = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($collapsed);
    }
}
