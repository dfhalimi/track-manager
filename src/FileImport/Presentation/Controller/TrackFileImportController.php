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

use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_PARTIAL;

final class TrackFileImportController extends AbstractController
{
    public function __construct(
        private readonly TrackFileImportDomainServiceInterface $trackFileImportDomainService
    ) {
    }

    #[Route(path: '/tracks/{trackUuid}/file/upload', name: 'file_import.presentation.upload', methods: [Request::METHOD_POST])]
    public function uploadAction(Request $request, string $trackUuid): Response
    {
        $uploadedFile = $this->resolveUploadedFile($request);
        if (!$uploadedFile instanceof UploadedFile) {
            return $this->redirectAfterUpload($request, $trackUuid);
        }

        try {
            $this->trackFileImportDomainService->uploadTrackFile(
                new UploadTrackFileInputDto($trackUuid, $uploadedFile)
            );
            $this->addFlash('success', 'Datei wurde hochgeladen.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectAfterUpload($request, $trackUuid);
    }

    #[Route(path: '/tracks/{trackUuid}/file/replace', name: 'file_import.presentation.replace', methods: [Request::METHOD_POST])]
    public function replaceAction(Request $request, string $trackUuid): Response
    {
        $uploadedFile = $this->resolveUploadedFile($request);
        if (!$uploadedFile instanceof UploadedFile) {
            return $this->redirectAfterUpload($request, $trackUuid);
        }

        try {
            $this->trackFileImportDomainService->replaceTrackFile(
                new ReplaceTrackFileInputDto($trackUuid, $uploadedFile)
            );
            $this->addFlash('success', 'Datei wurde ersetzt.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectAfterUpload($request, $trackUuid);
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

    private function resolveUploadedFile(Request $request): ?UploadedFile
    {
        $uploadedFile = $request->files->get('track_file');

        if ($uploadedFile instanceof UploadedFile) {
            if ($uploadedFile->isValid()) {
                return $uploadedFile;
            }

            $this->addFlash('error', $this->mapUploadErrorToMessage($uploadedFile->getError()));

            return null;
        }

        $maxFilesize         = UploadedFile::getMaxFilesize();
        $contentLengthHeader = $request->server->get('CONTENT_LENGTH', 0);
        $contentLength       = is_scalar($contentLengthHeader) ? (int) $contentLengthHeader : 0;

        if ($maxFilesize > 0 && $contentLength > $maxFilesize) {
            $this->addFlash('error', sprintf('Die Datei ist zu groß. PHP akzeptiert maximal %s.', $this->formatMegabytes($maxFilesize)));

            return null;
        }

        $this->addFlash('error', 'Bitte wähle eine Datei aus.');

        return null;
    }

    private function redirectAfterUpload(Request $request, string $trackUuid): Response
    {
        $redirectTo = $request->request->get('redirect_to');

        if (is_string($redirectTo) && str_starts_with($redirectTo, '/')) {
            return $this->redirect($redirectTo);
        }

        return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
    }

    private function mapUploadErrorToMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => sprintf(
                'Die Datei ist zu groß. PHP akzeptiert maximal %s.',
                $this->formatMegabytes(UploadedFile::getMaxFilesize())
            ),
            UPLOAD_ERR_PARTIAL    => 'Die Datei konnte nicht vollständig hochgeladen werden. Bitte versuche es erneut.',
            UPLOAD_ERR_NO_TMP_DIR => 'Der Upload ist aktuell nicht möglich, weil das temporäre Verzeichnis fehlt.',
            UPLOAD_ERR_CANT_WRITE => 'Die hochgeladene Datei konnte nicht gespeichert werden.',
            UPLOAD_ERR_EXTENSION  => 'Der Upload wurde durch eine PHP-Erweiterung abgebrochen.',
            default               => 'Beim Upload ist ein unbekannter Fehler aufgetreten.',
        };
    }

    private function formatMegabytes(int|float $bytes): string
    {
        return sprintf('%.0f MB', ceil(((float) $bytes) / 1024 / 1024));
    }
}
