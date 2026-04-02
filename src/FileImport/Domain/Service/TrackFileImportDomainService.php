<?php

declare(strict_types=1);

namespace App\FileImport\Domain\Service;

use App\FileImport\Domain\Dto\ReplaceTrackFileInputDto;
use App\FileImport\Domain\Dto\UploadTrackFileInputDto;
use App\FileImport\Domain\Entity\TrackFile;
use App\FileImport\Facade\Dto\TrackFileDto;
use App\FileImport\Infrastructure\Repository\TrackFileRepositoryInterface;
use App\FileImport\Infrastructure\Storage\TrackFileStorageInterface;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;
use ValueError;

readonly class TrackFileImportDomainService implements TrackFileImportDomainServiceInterface
{
    private const int MAX_AUDIO_FILE_SIZE_BYTES = 250 * 1024 * 1024;

    public function __construct(
        private TrackFileRepositoryInterface   $trackFileRepository,
        private TrackFileStorageInterface      $trackFileStorage,
        private TrackManagementFacadeInterface $trackManagementFacade
    ) {
    }

    public function uploadTrackFile(UploadTrackFileInputDto $input): TrackFileDto
    {
        $this->ensureTrackExists($input->trackUuid);
        $this->assertSupportedAudioFile($input->uploadedFile);

        $existingTrackFile = $this->trackFileRepository->findCurrentByTrackUuid($input->trackUuid);

        if ($existingTrackFile !== null) {
            return $this->replaceTrackFile(
                new ReplaceTrackFileInputDto($input->trackUuid, $input->uploadedFile)
            );
        }

        $extension  = $this->resolveExtension($input->uploadedFile);
        $storedFile = $this->trackFileStorage->storeUploadedFile(
            $input->uploadedFile,
            $this->trackFileStorage->buildStorageFilename($input->trackUuid, $extension)
        );

        $trackFile = new TrackFile();
        $trackFile->setUuid(Uuid::v7()->toRfc4122());
        $trackFile->setTrackUuid($input->trackUuid);
        $trackFile->setOriginalFilename($input->uploadedFile->getClientOriginalName());
        $trackFile->setStoredFilename($storedFile->storedFilename);
        $trackFile->setMimeType($storedFile->mimeType);
        $trackFile->setExtension($storedFile->extension);
        $trackFile->setSizeBytes($storedFile->sizeBytes);
        $trackFile->setUploadedAt(DateAndTimeService::getDateTimeImmutable());

        $this->trackFileRepository->save($trackFile);

        return $this->mapTrackFileToDto($trackFile);
    }

    public function replaceTrackFile(ReplaceTrackFileInputDto $input): TrackFileDto
    {
        $this->ensureTrackExists($input->trackUuid);
        $this->assertSupportedAudioFile($input->uploadedFile);

        $existingTrackFile = $this->trackFileRepository->findCurrentByTrackUuid($input->trackUuid);
        if ($existingTrackFile === null) {
            return $this->uploadTrackFile(new UploadTrackFileInputDto($input->trackUuid, $input->uploadedFile));
        }

        $extension  = $this->resolveExtension($input->uploadedFile);
        $storedFile = $this->trackFileStorage->replaceStoredFile(
            $existingTrackFile->getStoredFilename(),
            $input->uploadedFile,
            $this->trackFileStorage->buildStorageFilename($input->trackUuid, $extension)
        );

        $existingTrackFile->setOriginalFilename($input->uploadedFile->getClientOriginalName());
        $existingTrackFile->setStoredFilename($storedFile->storedFilename);
        $existingTrackFile->setMimeType($storedFile->mimeType);
        $existingTrackFile->setExtension($storedFile->extension);
        $existingTrackFile->setSizeBytes($storedFile->sizeBytes);
        $existingTrackFile->setUploadedAt(DateAndTimeService::getDateTimeImmutable());

        $this->trackFileRepository->save($existingTrackFile);

        return $this->mapTrackFileToDto($existingTrackFile);
    }

    public function getCurrentTrackFile(string $trackUuid): ?TrackFileDto
    {
        $trackFile = $this->trackFileRepository->findCurrentByTrackUuid($trackUuid);

        return $trackFile === null ? null : $this->mapTrackFileToDto($trackFile);
    }

    public function assertSupportedAudioFile(UploadedFile $file): void
    {
        $this->assertAudioFileSize($file);

        $extension = $this->resolveExtension($file);

        if (!in_array($extension, ['mp3', 'wav'], true)) {
            throw new ValueError('Only MP3 and WAV files are supported.');
        }
    }

    public function deleteCurrentTrackFile(string $trackUuid): void
    {
        $trackFile = $this->trackFileRepository->findCurrentByTrackUuid($trackUuid);

        if ($trackFile === null) {
            return;
        }

        $this->trackFileStorage->deleteStoredFile($trackFile->getStoredFilename());
        $this->trackFileRepository->remove($trackFile);
    }

    private function ensureTrackExists(string $trackUuid): void
    {
        if (!$this->trackManagementFacade->trackExists($trackUuid)) {
            throw new ValueError('Target track does not exist.');
        }
    }

    private function assertAudioFileSize(UploadedFile $file): void
    {
        $fileSize = (int) $file->getSize();

        if ($fileSize <= 0) {
            return;
        }

        if ($fileSize > self::MAX_AUDIO_FILE_SIZE_BYTES) {
            throw new ValueError(
                sprintf('Die Datei ist zu groß. Erlaubt sind maximal %d MB.', self::MAX_AUDIO_FILE_SIZE_BYTES / 1024 / 1024)
            );
        }
    }

    private function resolveExtension(UploadedFile $file): string
    {
        $extension = mb_strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));

        if ($extension === '') {
            throw new ValueError('Uploaded file extension could not be detected.');
        }

        return $extension;
    }

    private function mapTrackFileToDto(TrackFile $trackFile): TrackFileDto
    {
        return new TrackFileDto(
            $trackFile->getUuid(),
            $trackFile->getTrackUuid(),
            $trackFile->getOriginalFilename(),
            $trackFile->getStoredFilename(),
            $this->trackFileStorage->resolveStoragePath($trackFile->getStoredFilename()),
            $trackFile->getMimeType(),
            $trackFile->getExtension(),
            $trackFile->getSizeBytes(),
            $trackFile->getUploadedAt()
        );
    }
}
