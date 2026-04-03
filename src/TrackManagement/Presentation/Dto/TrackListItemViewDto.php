<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Dto;

readonly class TrackListItemViewDto
{
    /**
     * @param list<TrackProjectBadgeViewDto> $projectBadges
     */
    public function __construct(
        public string            $uuid,
        public int               $trackNumber,
        public string            $beatName,
        public string            $title,
        public ?string           $publishingName,
        public array             $projectBadges,
        public string            $bpmLabel,
        public string            $musicalKeyLabel,
        public string            $statusLabel,
        public string            $statusValue,
        public bool              $cancelled,
        public bool              $published,
        public int               $progress,
        public bool              $hasCurrentFile,
        public string            $uploadUrl,
        public ?TrackFileViewDto $trackFile,
        public string            $historyUrl,
        public string            $showUrl,
        public string            $editUrl,
        public string            $deleteUrl
    ) {
    }
}
