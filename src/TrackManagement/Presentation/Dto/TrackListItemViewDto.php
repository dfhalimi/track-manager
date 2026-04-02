<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Dto;

readonly class TrackListItemViewDto
{
    public function __construct(
        public string            $uuid,
        public int               $trackNumber,
        public string            $beatName,
        public string            $title,
        public ?string           $publishingName,
        public string            $bpmLabel,
        public string            $musicalKeyLabel,
        public string            $statusLabel,
        public string            $statusValue,
        public int               $progress,
        public bool              $hasCurrentFile,
        public string            $uploadUrl,
        public ?TrackFileViewDto $trackFile,
        public string            $showUrl,
        public string            $editUrl,
        public string            $deleteUrl
    ) {
    }
}
