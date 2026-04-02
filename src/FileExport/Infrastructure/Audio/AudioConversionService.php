<?php

declare(strict_types=1);

namespace App\FileExport\Infrastructure\Audio;

use Symfony\Component\Process\Process;
use ValueError;

readonly class AudioConversionService implements AudioConversionServiceInterface
{
    public function convertToMp3(string $sourcePath, string $targetPath): void
    {
        $this->convert($sourcePath, $targetPath, 'mp3');
    }

    public function convertToWav(string $sourcePath, string $targetPath): void
    {
        $this->convert($sourcePath, $targetPath, 'wav');
    }

    public function convert(string $sourcePath, string $targetPath, string $targetFormat): void
    {
        if (!in_array($targetFormat, ['mp3', 'wav'], true)) {
            throw new ValueError('Unsupported export format.');
        }

        $process = new Process([
            'ffmpeg',
            '-y',
            '-i',
            $sourcePath,
            $targetPath,
        ]);
        $process->mustRun();
    }
}
