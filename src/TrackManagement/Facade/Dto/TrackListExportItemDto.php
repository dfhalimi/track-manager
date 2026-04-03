<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\Dto;

readonly class TrackListExportItemDto
{
    /**
     * @param list<float>  $bpms
     * @param list<string> $musicalKeys
     */
    public function __construct(
        public string  $uuid,
        public int     $trackNumber,
        public string  $beatName,
        public string  $title,
        public ?string $publishingName,
        public array   $bpms,
        public array   $musicalKeys,
        public int     $progress,
        public string  $status,
        public bool    $cancelled,
        public bool    $published
    ) {
    }
}
