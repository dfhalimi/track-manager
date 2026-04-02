<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

readonly class ProjectDetailViewDto
{
    /**
     * @param list<ProjectTrackAssignmentViewDto> $tracks
     * @param list<ProjectTrackOptionViewDto> $availableTracks
     */
    public function __construct(
        public string $uuid,
        public string $title,
        public string $categoryName,
        public array $tracks,
        public array $availableTracks,
        public ?ProjectMediaAssetViewDto $mediaAsset,
        public string $backToListUrl,
        public string $tracksIndexUrl,
        public string $editUrl,
        public string $deleteUrl,
        public string $addTrackUrl,
        public string $reorderUrl
    ) {
    }
}
