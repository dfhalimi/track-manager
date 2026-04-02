<?php

declare(strict_types=1);

namespace App\FileExport\Presentation\Controller;

use App\FileExport\Domain\Dto\ExportProjectFilesInputDto;
use App\FileExport\Domain\Service\ProjectFileExportDomainServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectFileExportController extends AbstractController
{
    public function __construct(
        private readonly ProjectFileExportDomainServiceInterface $projectFileExportDomainService
    ) {
    }

    #[Route(path: '/projects/{projectUuid}/export/{format}', name: 'file_export.presentation.project_export', methods: [Request::METHOD_GET])]
    public function exportAction(string $projectUuid, string $format): BinaryFileResponse
    {
        $exportedProjectArchive = $this->projectFileExportDomainService->exportProjectFiles(
            new ExportProjectFilesInputDto($projectUuid, $format)
        );

        $response = new BinaryFileResponse($exportedProjectArchive->filePath);
        $response->headers->set('Content-Type', $exportedProjectArchive->mimeType);
        $response->setContentDisposition('attachment', $exportedProjectArchive->downloadFilename);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
