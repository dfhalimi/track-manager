<?php

declare(strict_types=1);

use App\FileImport\Domain\Dto\UploadTrackFileInputDto;
use App\FileImport\Domain\Entity\TrackFile;
use App\FileImport\Domain\Service\TrackFileImportDomainService;
use App\FileImport\Infrastructure\Dto\StoredFileDto;
use App\FileImport\Infrastructure\Repository\TrackFileRepositoryInterface;
use App\FileImport\Infrastructure\Storage\TrackFileStorageInterface;
use App\TrackManagement\Facade\Dto\TrackChecklistDto;
use App\TrackManagement\Facade\Dto\TrackDto;
use App\TrackManagement\Facade\Dto\TrackExportDataDto;
use App\TrackManagement\Facade\Dto\TrackNamingDto;
use App\TrackManagement\Facade\Dto\TrackSelectionDto;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

describe('TrackFileImportDomainService', function (): void {
    it('accepts mp3 uploads when Symfony reports an alias extension', function (): void {
        $storage = new RecordingTrackFileStorage();
        $service = new TrackFileImportDomainService(
            new InMemoryTrackFileRepository(),
            $storage,
            new ExistingTrackManagementFacadeStub(['track-1'])
        );

        $uploadedFile = createUploadedAudioFile(
            'mp3',
            1024,
            'mpga',
            'audio/mpeg',
            'audio/mpeg'
        );

        $trackFile = $service->uploadTrackFile(new UploadTrackFileInputDto('track-1', $uploadedFile));

        expect($trackFile->extension)->toBe('mp3');
        expect($storage->lastStorageFilename)->toBe('track-1.mp3');
    });

    it('rejects audio files above the application upload limit', function (): void {
        $service = new TrackFileImportDomainService(
            new InMemoryTrackFileRepository(),
            new RecordingTrackFileStorage(),
            new ExistingTrackManagementFacadeStub(['track-1'])
        );

        $action = static fn () => $service->uploadTrackFile(
            new UploadTrackFileInputDto('track-1', createUploadedAudioFile('wav', 251 * 1024 * 1024))
        );

        expect($action)->toThrow(ValueError::class, 'Die Datei ist zu groß. Erlaubt sind maximal 250 MB.');
    });
    it('rejects unsupported audio files', function (): void {
        $service = new TrackFileImportDomainService(
            new InMemoryTrackFileRepository(),
            new RecordingTrackFileStorage(),
            new ExistingTrackManagementFacadeStub(['track-1'])
        );

        $action = static fn () => $service->uploadTrackFile(
            new UploadTrackFileInputDto(
                'track-1',
                createUploadedAudioFile('ogg', 1024, 'ogg', 'audio/ogg', 'audio/ogg')
            )
        );

        expect($action)->toThrow(ValueError::class, 'Only MP3 and WAV files are supported.');
    });
});

function createUploadedAudioFile(
    string  $extension,
    int     $sizeBytes,
    ?string $guessedExtension = null,
    ?string $mimeType = null,
    ?string $clientMimeType = null
): UploadedFile {
    $path = tempnam(sys_get_temp_dir(), 'track-file-test-');
    if ($path === false) {
        throw new RuntimeException('Temporary file could not be created.');
    }

    file_put_contents($path, 'audio');

    return new class($path, $extension, $sizeBytes, $guessedExtension, $mimeType, $clientMimeType) extends UploadedFile {
        public function __construct(
            string                   $path,
            string                   $extension,
            private readonly int     $sizeBytes,
            private readonly ?string $guessedExtension,
            private readonly ?string $mimeType,
            private readonly ?string $clientMimeType
        ) {
            parent::__construct($path, 'demo.' . $extension, $clientMimeType ?? 'audio/' . $extension, null, true);
        }

        public function getSize(): int
        {
            return $this->sizeBytes;
        }

        public function guessExtension(): ?string
        {
            return $this->guessedExtension;
        }

        public function getMimeType(): ?string
        {
            return $this->mimeType;
        }

        public function getClientMimeType(): string
        {
            return $this->clientMimeType ?? parent::getClientMimeType();
        }
    };
}

final class InMemoryTrackFileRepository implements TrackFileRepositoryInterface
{
    public function save(TrackFile $trackFile): void
    {
    }

    public function remove(TrackFile $trackFile): void
    {
    }

    public function findCurrentByTrackUuid(string $trackUuid): ?TrackFile
    {
        return null;
    }
}

final class RecordingTrackFileStorage implements TrackFileStorageInterface
{
    public ?string $lastStorageFilename = null;

    public function storeUploadedFile(UploadedFile $file, string $storageFilename): StoredFileDto
    {
        $this->lastStorageFilename = $storageFilename;

        return new StoredFileDto(
            $storageFilename,
            '/tmp/' . $storageFilename,
            (string) pathinfo($storageFilename, PATHINFO_EXTENSION),
            (string) ($file->getClientMimeType() ?: 'application/octet-stream'),
            (int) $file->getSize()
        );
    }

    public function replaceStoredFile(string $oldFilename, UploadedFile $newFile, string $newStorageFilename): StoredFileDto
    {
        return $this->storeUploadedFile($newFile, $newStorageFilename);
    }

    public function deleteStoredFile(string $storedFilename): void
    {
    }

    public function buildStorageFilename(string $trackUuid, string $extension): string
    {
        return $trackUuid . '.' . $extension;
    }

    public function resolveStoragePath(string $storedFilename): string
    {
        return '/tmp/' . $storedFilename;
    }
}

final readonly class ExistingTrackManagementFacadeStub implements TrackManagementFacadeInterface
{
    /**
     * @param list<string> $existingTrackUuids
     */
    public function __construct(
        private array $existingTrackUuids
    ) {
    }

    public function getTrackByUuid(string $trackUuid): TrackDto
    {
        return new TrackDto(
            $trackUuid,
            1,
            'Beat',
            'Title',
            null,
            [120.0],
            ['C Maj'],
            null,
            null,
            false,
            DateAndTimeService::getDateTimeImmutable(),
            DateAndTimeService::getDateTimeImmutable()
        );
    }

    public function getTrackByTrackNumber(int $trackNumber): ?TrackDto
    {
        throw new BadMethodCallException();
    }

    public function getTrackExportData(string $trackUuid): TrackExportDataDto
    {
        throw new BadMethodCallException();
    }

    public function getTrackNamingData(string $trackUuid): TrackNamingDto
    {
        throw new BadMethodCallException();
    }

    public function trackExists(string $trackUuid): bool
    {
        return in_array($trackUuid, $this->existingTrackUuids, true);
    }

    public function getChecklistByTrackUuid(string $trackUuid): TrackChecklistDto
    {
        throw new BadMethodCallException();
    }

    public function getAllTracksForSelection(): array
    {
        return array_map(
            static fn (string $trackUuid): TrackSelectionDto => new TrackSelectionDto($trackUuid, 1, 'Beat', 'Title', null),
            $this->existingTrackUuids
        );
    }
}
