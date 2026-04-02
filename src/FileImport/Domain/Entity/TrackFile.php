<?php

declare(strict_types=1);

namespace App\FileImport\Domain\Entity;

use App\FileImport\Infrastructure\Repository\TrackFileRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrackFileRepository::class)]
#[ORM\Table(name: 'track_files')]
#[ORM\UniqueConstraint(name: 'uniq_track_files_track_uuid', columns: ['track_uuid'])]
class TrackFile
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID, unique: true)]
    private string $uuid;

    #[ORM\Column(name: 'track_uuid', type: Types::GUID)]
    private string $trackUuid;

    #[ORM\Column(name: 'original_filename', type: Types::STRING, length: 255)]
    private string $originalFilename;

    #[ORM\Column(name: 'stored_filename', type: Types::STRING, length: 255)]
    private string $storedFilename;

    #[ORM\Column(name: 'mime_type', type: Types::STRING, length: 255)]
    private string $mimeType;

    #[ORM\Column(type: Types::STRING, length: 16)]
    private string $extension;

    #[ORM\Column(name: 'size_bytes', type: Types::INTEGER)]
    private int $sizeBytes;

    #[ORM\Column(name: 'uploaded_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $uploadedAt;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getTrackUuid(): string
    {
        return $this->trackUuid;
    }

    public function setTrackUuid(string $trackUuid): void
    {
        $this->trackUuid = $trackUuid;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function getStoredFilename(): string
    {
        return $this->storedFilename;
    }

    public function setStoredFilename(string $storedFilename): void
    {
        $this->storedFilename = $storedFilename;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function setSizeBytes(int $sizeBytes): void
    {
        $this->sizeBytes = $sizeBytes;
    }

    public function getUploadedAt(): DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(DateTimeImmutable $uploadedAt): void
    {
        $this->uploadedAt = $uploadedAt;
    }
}
