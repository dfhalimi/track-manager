<?php

declare(strict_types=1);

namespace App\ActivityHistory\Domain\Entity;

use App\ActivityHistory\Infrastructure\Repository\ActivityHistoryEntryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityHistoryEntryRepository::class)]
#[ORM\Table(name: 'activity_history_entries')]
#[ORM\Index(name: 'idx_activity_history_entity_occurred_at', columns: ['entity_type', 'entity_uuid', 'occurred_at'])]
class ActivityHistoryEntry
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID, unique: true)]
    private string $uuid;

    #[ORM\Column(name: 'entity_type', type: Types::STRING, length: 32)]
    private string $entityType;

    #[ORM\Column(name: 'entity_uuid', type: Types::GUID)]
    private string $entityUuid;

    #[ORM\Column(name: 'event_type', type: Types::STRING, length: 64)]
    private string $eventType;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $summary;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $details = [];

    #[ORM\Column(name: 'occurred_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $occurredAt;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): void
    {
        $this->entityType = $entityType;
    }

    public function getEntityUuid(): string
    {
        return $this->entityUuid;
    }

    public function setEntityUuid(string $entityUuid): void
    {
        $this->entityUuid = $entityUuid;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): void
    {
        $this->eventType = $eventType;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): void
    {
        $this->summary = $summary;
    }

    /**
     * @return list<string>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param list<string> $details
     */
    public function setDetails(array $details): void
    {
        $this->details = $details;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(DateTimeImmutable $occurredAt): void
    {
        $this->occurredAt = $occurredAt;
    }
}
