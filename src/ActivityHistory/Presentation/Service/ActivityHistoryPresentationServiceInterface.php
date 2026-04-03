<?php

declare(strict_types=1);

namespace App\ActivityHistory\Presentation\Service;

use App\ActivityHistory\Presentation\Dto\ActivityHistoryModalViewDto;

interface ActivityHistoryPresentationServiceInterface
{
    public function buildTrackHistoryModalViewDto(string $trackUuid, int $limit = 50): ActivityHistoryModalViewDto;

    public function buildProjectHistoryModalViewDto(string $projectUuid, int $limit = 50): ActivityHistoryModalViewDto;
}
