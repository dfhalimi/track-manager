<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Entity;

use App\TrackManagement\Infrastructure\Repository\TrackRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrackRepository::class)]
#[ORM\Table(name: 'tracks')]
#[ORM\UniqueConstraint(name: 'uniq_tracks_track_number', columns: ['track_number'])]
class Track
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID, unique: true)]
    private string $uuid;

    #[ORM\Column(name: 'track_number', type: Types::INTEGER, unique: true)]
    private int $trackNumber;

    #[ORM\Column(name: 'beat_name', type: Types::STRING, length: 255)]
    private string $beatName;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(name: 'publishing_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $publishingName = null;

    /**
     * @var list<int>
     */
    #[ORM\Column(name: 'bpms', type: Types::JSON)]
    private array $bpms = [];

    #[ORM\Column(name: 'musical_key', type: Types::STRING, length: 32)]
    private string $musicalKey;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $isrc = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getTrackNumber(): int
    {
        return $this->trackNumber;
    }

    public function setTrackNumber(int $trackNumber): void
    {
        $this->trackNumber = $trackNumber;
    }

    public function getBeatName(): string
    {
        return $this->beatName;
    }

    public function setBeatName(string $beatName): void
    {
        $this->beatName = $beatName;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getPublishingName(): ?string
    {
        return $this->publishingName;
    }

    public function setPublishingName(?string $publishingName): void
    {
        $this->publishingName = $publishingName;
    }

    /**
     * @return list<int>
     */
    public function getBpms(): array
    {
        return $this->bpms;
    }

    /**
     * @param list<int> $bpms
     */
    public function setBpms(array $bpms): void
    {
        $this->bpms = $bpms;
    }

    public function getMusicalKey(): string
    {
        return $this->musicalKey;
    }

    public function setMusicalKey(string $musicalKey): void
    {
        $this->musicalKey = $musicalKey;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getIsrc(): ?string
    {
        return $this->isrc;
    }

    public function setIsrc(?string $isrc): void
    {
        $this->isrc = $isrc;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
