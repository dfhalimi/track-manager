<?php

declare(strict_types=1);

namespace App\FileImport\Presentation\Controller;

use App\FileImport\Domain\Dto\ReplaceTrackFileInputDto;
use App\FileImport\Domain\Dto\UploadTrackFileInputDto;
use App\FileImport\Domain\Service\TrackFileImportDomainServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class TrackFileImportController extends AbstractController
{
    public function __construct(
        private readonly TrackFileImportDomainServiceInterface $trackFileImportDomainService
    ) {
    }

    #[Route(path: '/tracks/{trackUuid}/file/upload', name: 'file_import.presentation.upload', methods: [Request::METHOD_POST])]
    public function uploadAction(Request $request, string $trackUuid): Response
    {
        $uploadedFile = $request->files->get('track_file');

        if (!$uploadedFile instanceof UploadedFile) {
            $this->addFlash('error', 'Bitte wähle eine Datei aus.');

            return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
        }

        try {
            $this->trackFileImportDomainService->uploadTrackFile(
                new UploadTrackFileInputDto($trackUuid, $uploadedFile)
            );
            $this->addFlash('success', 'Datei wurde hochgeladen.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
    }

    #[Route(path: '/tracks/{trackUuid}/file/replace', name: 'file_import.presentation.replace', methods: [Request::METHOD_POST])]
    public function replaceAction(Request $request, string $trackUuid): Response
    {
        $uploadedFile = $request->files->get('track_file');

        if (!$uploadedFile instanceof UploadedFile) {
            $this->addFlash('error', 'Bitte wähle eine Datei aus.');

            return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
        }

        try {
            $this->trackFileImportDomainService->replaceTrackFile(
                new ReplaceTrackFileInputDto($trackUuid, $uploadedFile)
            );
            $this->addFlash('success', 'Datei wurde ersetzt.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
    }

    #[Route(path: '/tracks/{trackUuid}/file/play', name: 'file_import.presentation.play', methods: [Request::METHOD_GET])]
    public function playAction(string $trackUuid): BinaryFileResponse
    {
        $trackFile = $this->trackFileImportDomainService->getCurrentTrackFile($trackUuid);

        if ($trackFile === null) {
            throw $this->createNotFoundException('Track file not found.');
        }

        $response = new BinaryFileResponse($trackFile->storagePath);
        $response->headers->set('Content-Type', $trackFile->mimeType);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $trackFile->originalFilename);

        return $response;
    }
}
