<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Presentation\Controller;

use App\MediaAssetManagement\Facade\MediaAssetManagementFacadeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class ProjectMediaAssetController extends AbstractController
{
    public function __construct(
        private readonly MediaAssetManagementFacadeInterface $mediaAssetManagementFacade
    ) {
    }

    #[Route(path: '/projects/{projectUuid}/media/replace', name: 'media_asset_management.presentation.replace', methods: [Request::METHOD_POST])]
    public function replaceAction(Request $request, string $projectUuid): Response
    {
        $uploadedFile = $request->files->get('project_image');

        if (!$uploadedFile instanceof UploadedFile) {
            $this->addFlash('error', 'Bitte wähle ein Bild aus.');

            return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
        }

        try {
            $this->mediaAssetManagementFacade->replaceProjectMediaAsset($projectUuid, $uploadedFile);
            $this->addFlash('success', 'Projektbild wurde ersetzt.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
    }

    #[Route(path: '/projects/{projectUuid}/media/preview', name: 'media_asset_management.presentation.preview', methods: [Request::METHOD_GET])]
    public function previewAction(string $projectUuid): BinaryFileResponse
    {
        $mediaAsset = $this->mediaAssetManagementFacade->getCurrentProjectMediaAssetByProjectUuid($projectUuid);

        if ($mediaAsset === null) {
            throw $this->createNotFoundException('Project image not found.');
        }

        $response = new BinaryFileResponse($mediaAsset->storagePath);
        $response->headers->set('Content-Type', $mediaAsset->mimeType);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $mediaAsset->originalFilename);

        return $response;
    }

    #[Route(path: '/projects/{projectUuid}/media/export/{format}', name: 'media_asset_management.presentation.export', methods: [Request::METHOD_GET])]
    public function exportAction(string $projectUuid, string $format): BinaryFileResponse
    {
        $exportedMediaAsset = $this->mediaAssetManagementFacade->exportProjectMediaAsset($projectUuid, $format);

        $response = new BinaryFileResponse($exportedMediaAsset->filePath);
        $response->headers->set('Content-Type', $exportedMediaAsset->mimeType);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $exportedMediaAsset->downloadFilename);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
