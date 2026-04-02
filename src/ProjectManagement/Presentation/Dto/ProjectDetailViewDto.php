<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

readonly class ProjectDetailViewDto
{
    /**
     * @param list<ProjectTrackAssignmentViewDto> $tracks
     * @param list<ProjectTrackOptionViewDto>     $availableTracks
     */
    public function __construct(
        public string                    $uuid,
        public string                    $title,
        public string                    $categoryName,
        public string                    $createdAt,
        public bool                      $cancelled,
        public bool                      $hasExportableTracks,
        public string                    $exportAllMp3Url,
        public string                    $exportAllWavUrl,
        public array                     $tracks,
        public array                     $availableTracks,
        public string                    $addTrackSuggestionsUrl,
        public ?ProjectMediaAssetViewDto $mediaAsset,
        public string                    $backToListUrl,
        public string                    $tracksIndexUrl,
        public string                    $editUrl,
        public string                    $cancelUrl,
        public string                    $reactivateUrl,
        public string                    $addTrackUrl,
        public string                    $reorderUrl
    ) {
    }
}
