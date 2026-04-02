<?php

declare(strict_types=1);

namespace App\FileImport\Facade;

use App\FileImport\Domain\Service\TrackFileImportDomainServiceInterface;
use App\FileImport\Facade\Dto\TrackFileDto;

readonly class FileImportFacade implements FileImportFacadeInterface
{
    public function __construct(
        private TrackFileImportDomainServiceInterface $trackFileImportDomainService
    ) {
    }

    public function getCurrentTrackFileByTrackUuid(string $trackUuid): ?TrackFileDto
    {
        return $this->trackFileImportDomainService->getCurrentTrackFile($trackUuid);
    }

    public function deleteCurrentTrackFileByTrackUuid(string $trackUuid): void
    {
        $this->trackFileImportDomainService->deleteCurrentTrackFile($trackUuid);
    }
}
