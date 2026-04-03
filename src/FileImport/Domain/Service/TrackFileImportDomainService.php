<?php

declare(strict_types=1);

namespace App\FileImport\Domain\Service;

use App\FileImport\Domain\Dto\ReplaceTrackFileInputDto;
use App\FileImport\Domain\Dto\UploadTrackFileInputDto;
use App\FileImport\Domain\Entity\TrackFile;
use App\FileImport\Facade\Dto\TrackFileDto;
use App\FileImport\Facade\SymfonyEvent\TrackFileReplacedSymfonyEvent;
use App\FileImport\Facade\SymfonyEvent\TrackFileUploadedSymfonyEvent;
use App\FileImport\Infrastructure\Repository\TrackFileRepositoryInterface;
use App\FileImport\Infrastructure\Storage\TrackFileStorageInterface;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use ValueError;

readonly class TrackFileImportDomainService implements TrackFileImportDomainServiceInterface
{
    private const int MAX_AUDIO_FILE_SIZE_BYTES = 250 * 1024 * 1024;

    /**
     * @var array<string, string>
     */
    private const array AUDIO_EXTENSION_ALIASES = [
        'mp3'            => 'mp3',
        'mpeg'           => 'mp3',
        'mpga'           => 'mp3',
        'audio/mpeg'     => 'mp3',
        'audio/mp3'      => 'mp3',
        'audio/mpa'      => 'mp3',
        'wav'            => 'wav',
        'wave'           => 'wav',
        'audio/wav'      => 'wav',
        'audio/wave'     => 'wav',
        'audio/x-wav'    => 'wav',
        'audio/vnd.wave' => 'wav',
    ];

    public function __construct(
        private TrackFileRepositoryInterface   $trackFileRepository,
        private TrackFileStorageInterface      $trackFileStorage,
        private TrackManagementFacadeInterface $trackManagementFacade,
        private EventDispatcherInterface       $eventDispatcher
    ) {
    }

    public function uploadTrackFile(UploadTrackFileInputDto $input): TrackFileDto
    {
        $this->ensureTrackExists($input->trackUuid);
        $extension = $this->resolveSupportedAudioExtension($input->uploadedFile);

        $existingTrackFile = $this->trackFileRepository->findCurrentByTrackUuid($input->trackUuid);

        if ($existingTrackFile !== null) {
            return $this->replaceTrackFile(
                new ReplaceTrackFileInputDto($input->trackUuid, $input->uploadedFile)
            );
        }

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
        $occurredAt = DateAndTimeService::getDateTimeImmutable();
        $trackFile->setUploadedAt($occurredAt);

        $this->trackFileRepository->save($trackFile);
        $this->eventDispatcher->dispatch(
            new TrackFileUploadedSymfonyEvent(
                $input->trackUuid,
                $trackFile->getOriginalFilename(),
                $occurredAt
            )
        );

        return $this->mapTrackFileToDto($trackFile);
    }

    public function replaceTrackFile(ReplaceTrackFileInputDto $input): TrackFileDto
    {
        $this->ensureTrackExists($input->trackUuid);
        $extension = $this->resolveSupportedAudioExtension($input->uploadedFile);

        $existingTrackFile = $this->trackFileRepository->findCurrentByTrackUuid($input->trackUuid);
        if ($existingTrackFile === null) {
            return $this->uploadTrackFile(new UploadTrackFileInputDto($input->trackUuid, $input->uploadedFile));
        }

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
        $occurredAt = DateAndTimeService::getDateTimeImmutable();
        $existingTrackFile->setUploadedAt($occurredAt);

        $this->trackFileRepository->save($existingTrackFile);
        $this->eventDispatcher->dispatch(
            new TrackFileReplacedSymfonyEvent(
                $input->trackUuid,
                $existingTrackFile->getOriginalFilename(),
                $occurredAt
            )
        );

        return $this->mapTrackFileToDto($existingTrackFile);
    }

    public function getCurrentTrackFile(string $trackUuid): ?TrackFileDto
    {
        $trackFile = $this->trackFileRepository->findCurrentByTrackUuid($trackUuid);

        return $trackFile === null ? null : $this->mapTrackFileToDto($trackFile);
    }

    public function assertSupportedAudioFile(UploadedFile $file): void
    {
        $this->resolveSupportedAudioExtension($file);
    }

    private function resolveSupportedAudioExtension(UploadedFile $file): string
    {
        $this->assertAudioFileSize($file);

        $extension = $this->resolveExtension($file);

        if (!in_array($extension, ['mp3', 'wav'], true)) {
            throw new ValueError('Only MP3 and WAV files are supported.');
        }

        return $extension;
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

        if ($this->trackManagementFacade->getTrackByUuid($trackUuid)->cancelled) {
            throw new ValueError('Archivierte Tracks koennen keine neuen Dateien erhalten.');
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
        $candidates = [
            $file->guessExtension(),
            $file->getClientOriginalExtension(),
            $file->getMimeType(),
            $file->getClientMimeType(),
        ];
        $fallbackExtension = null;

        foreach ($candidates as $candidate) {
            $normalizedExtension = $this->normalizeAudioExtension($candidate);
            if ($normalizedExtension !== null) {
                return $normalizedExtension;
            }

            $fallbackExtension ??= $this->normalizeExtensionCandidate($candidate);
        }

        if ($fallbackExtension !== null) {
            return $fallbackExtension;
        }

        throw new ValueError('Uploaded file extension could not be detected.');
    }

    private function normalizeAudioExtension(?string $candidate): ?string
    {
        if ($candidate === null) {
            return null;
        }

        $normalizedCandidate = mb_strtolower(trim($candidate));
        if ($normalizedCandidate === '') {
            return null;
        }

        return self::AUDIO_EXTENSION_ALIASES[$normalizedCandidate] ?? null;
    }

    private function normalizeExtensionCandidate(?string $candidate): ?string
    {
        if ($candidate === null) {
            return null;
        }

        $normalizedCandidate = mb_strtolower(trim($candidate));

        return $normalizedCandidate === '' ? null : $normalizedCandidate;
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
