<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Entity;

use App\ProjectManagement\Infrastructure\Repository\ProjectTrackAssignmentRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectTrackAssignmentRepository::class)]
#[ORM\Table(name: 'project_track_assignments')]
#[ORM\UniqueConstraint(name: 'uniq_project_track_assignments_project_track', columns: ['project_uuid', 'track_uuid'])]
#[ORM\Index(name: 'idx_project_track_assignments_project_uuid', columns: ['project_uuid'])]
#[ORM\Index(name: 'idx_project_track_assignments_track_uuid', columns: ['track_uuid'])]
class ProjectTrackAssignment
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID, unique: true)]
    private string $uuid;

    #[ORM\Column(name: 'project_uuid', type: Types::GUID)]
    private string $projectUuid;

    #[ORM\Column(name: 'track_uuid', type: Types::GUID)]
    private string $trackUuid;

    #[ORM\Column(type: Types::INTEGER)]
    private int $position;

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

    public function getProjectUuid(): string
    {
        return $this->projectUuid;
    }

    public function setProjectUuid(string $projectUuid): void
    {
        $this->projectUuid = $projectUuid;
    }

    public function getTrackUuid(): string
    {
        return $this->trackUuid;
    }

    public function setTrackUuid(string $trackUuid): void
    {
        $this->trackUuid = $trackUuid;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
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
