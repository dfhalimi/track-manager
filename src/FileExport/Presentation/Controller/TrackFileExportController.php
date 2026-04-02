<?php

declare(strict_types=1);

namespace App\FileExport\Presentation\Controller;

use App\FileExport\Domain\Dto\ExportTrackFileInputDto;
use App\FileExport\Domain\Service\TrackFileExportDomainServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TrackFileExportController extends AbstractController
{
    public function __construct(
        private readonly TrackFileExportDomainServiceInterface $trackFileExportDomainService
    ) {
    }

    #[Route(path: '/tracks/{trackUuid}/export/{format}', name: 'file_export.presentation.export', methods: [Request::METHOD_GET])]
    public function exportAction(string $trackUuid, string $format): BinaryFileResponse
    {
        $exportedTrackFile = $this->trackFileExportDomainService->exportTrackFile(
            new ExportTrackFileInputDto($trackUuid, $format)
        );

        $response = new BinaryFileResponse($exportedTrackFile->filePath);
        $response->headers->set('Content-Type', $exportedTrackFile->mimeType);
        $response->setContentDisposition('attachment', $exportedTrackFile->downloadFilename);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
