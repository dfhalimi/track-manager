<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Entity;

use App\ProjectManagement\Infrastructure\Repository\ProjectRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'projects')]
#[ORM\Index(name: 'idx_projects_category_uuid', columns: ['category_uuid'])]
#[ORM\UniqueConstraint(name: 'uniq_projects_normalized_title', columns: ['normalized_title'])]
class Project
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID, unique: true)]
    private string $uuid;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(name: 'normalized_title', type: Types::STRING, length: 255)]
    private string $normalizedTitle;

    #[ORM\Column(name: 'category_uuid', type: Types::GUID)]
    private string $categoryUuid;

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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getNormalizedTitle(): string
    {
        return $this->normalizedTitle;
    }

    public function setNormalizedTitle(string $normalizedTitle): void
    {
        $this->normalizedTitle = $normalizedTitle;
    }

    public function getCategoryUuid(): string
    {
        return $this->categoryUuid;
    }

    public function setCategoryUuid(string $categoryUuid): void
    {
        $this->categoryUuid = $categoryUuid;
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
