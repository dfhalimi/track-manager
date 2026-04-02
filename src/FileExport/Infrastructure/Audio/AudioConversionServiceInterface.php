<?php

declare(strict_types=1);

namespace App\FileExport\Infrastructure\Audio;

interface AudioConversionServiceInterface
{
    public function convertToMp3(string $sourcePath, string $targetPath): void;

    public function convertToWav(string $sourcePath, string $targetPath): void;

    public function convert(string $sourcePath, string $targetPath, string $targetFormat): void;
}
