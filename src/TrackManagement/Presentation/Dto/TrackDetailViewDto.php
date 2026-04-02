<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Dto;

readonly class TrackDetailViewDto
{
    /**
     * @param list<ChecklistItemViewDto> $checklistItems
     * @param list<TrackProjectMembershipViewDto> $projectMemberships
     */
    public function __construct(
        public string            $uuid,
        public int               $trackNumber,
        public string            $beatName,
        public string            $title,
        public ?string           $publishingName,
        public string            $bpmLabel,
        public string            $musicalKey,
        public ?string           $notes,
        public ?string           $isrc,
        public int               $progress,
        public string            $statusLabel,
        public string            $statusValue,
        public array             $checklistItems,
        public array             $projectMemberships,
        public ?TrackFileViewDto $trackFile,
        public string            $backToListUrl,
        public string            $editUrl,
        public string            $deleteUrl,
        public string            $checklistAddUrl,
        public string            $checklistReorderUrl
    ) {
    }
}
