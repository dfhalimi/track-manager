<?php

declare(strict_types=1);

namespace App\CsvExport\Presentation\Controller;

use App\CsvExport\Facade\CsvExportFacadeInterface;
use App\ProjectManagement\Facade\Dto\ProjectListFilterInputDto;
use App\TrackManagement\Facade\Dto\TrackListFilterInputDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CsvExportController extends AbstractController
{
    public function __construct(
        private readonly CsvExportFacadeInterface $csvExportFacade
    ) {
    }

    #[Route(path: '/tracks/export/csv', name: 'csv_export.presentation.tracks', methods: [Request::METHOD_GET])]
    public function exportTracksAction(Request $request): BinaryFileResponse
    {
        $download = $this->csvExportFacade->exportTracks(
            new TrackListFilterInputDto(
                $request->query->getString('q', ''),
                $request->query->getString('status', ''),
                $request->query->getString('cancelled', ''),
                $request->query->getString('sortBy', 'updatedAt'),
                $request->query->getString('sortDirection', 'DESC')
            )
        );

        $response = new BinaryFileResponse($download->filePath);
        $response->headers->set('Content-Type', $download->mimeType);
        $response->setContentDisposition('attachment', $download->downloadFilename);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route(path: '/projects/export/csv', name: 'csv_export.presentation.projects', methods: [Request::METHOD_GET])]
    public function exportProjectsAction(Request $request): BinaryFileResponse
    {
        $download = $this->csvExportFacade->exportProjects(
            new ProjectListFilterInputDto(
                $request->query->getString('q', ''),
                $request->query->getString('category', ''),
                $request->query->getString('cancelled', ''),
                $request->query->getString('sortBy', 'updatedAt'),
                $request->query->getString('sortDirection', 'DESC')
            )
        );

        $response = new BinaryFileResponse($download->filePath);
        $response->headers->set('Content-Type', $download->mimeType);
        $response->setContentDisposition('attachment', $download->downloadFilename);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
