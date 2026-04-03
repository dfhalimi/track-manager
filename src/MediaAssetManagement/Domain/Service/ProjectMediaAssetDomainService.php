<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Domain\Service;

use App\MediaAssetManagement\Domain\Entity\ProjectMediaAsset;
use App\MediaAssetManagement\Facade\Dto\ProjectMediaAssetDto;
use App\MediaAssetManagement\Facade\SymfonyEvent\ProjectImageReplacedSymfonyEvent;
use App\MediaAssetManagement\Facade\SymfonyEvent\ProjectImageUploadedSymfonyEvent;
use App\MediaAssetManagement\Infrastructure\Repository\ProjectMediaAssetRepositoryInterface;
use App\MediaAssetManagement\Infrastructure\Storage\ProjectMediaAssetStorageInterface;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use ValueError;

readonly class ProjectMediaAssetDomainService implements ProjectMediaAssetDomainServiceInterface
{
    public function __construct(
        private ProjectMediaAssetRepositoryInterface $projectMediaAssetRepository,
        private ProjectMediaAssetStorageInterface    $projectMediaAssetStorage,
        private ProjectManagementFacadeInterface     $projectManagementFacade,
        private EventDispatcherInterface             $eventDispatcher
    ) {
    }

    public function uploadProjectMediaAsset(string $projectUuid, UploadedFile $uploadedFile): ProjectMediaAssetDto
    {
        $this->ensureProjectExists($projectUuid);
        $this->assertSupportedImageFile($uploadedFile);

        $existingAsset = $this->projectMediaAssetRepository->findCurrentByProjectUuid($projectUuid);
        if ($existingAsset instanceof ProjectMediaAsset) {
            return $this->replaceProjectMediaAsset($projectUuid, $uploadedFile);
        }

        $extension  = $this->resolveExtension($uploadedFile);
        $storedFile = $this->projectMediaAssetStorage->storeUploadedFile(
            $uploadedFile,
            $this->projectMediaAssetStorage->buildStorageFilename($projectUuid, $extension)
        );

        $mediaAsset = new ProjectMediaAsset();
        $mediaAsset->setUuid(Uuid::v7()->toRfc4122());
        $mediaAsset->setProjectUuid($projectUuid);
        $mediaAsset->setOriginalFilename($uploadedFile->getClientOriginalName());
        $mediaAsset->setStoredFilename($storedFile->storedFilename);
        $mediaAsset->setMimeType($storedFile->mimeType);
        $mediaAsset->setExtension($storedFile->extension);
        $mediaAsset->setSizeBytes($storedFile->sizeBytes);
        $mediaAsset->setWidthPixels($storedFile->widthPixels);
        $mediaAsset->setHeightPixels($storedFile->heightPixels);
        $occurredAt = DateAndTimeService::getDateTimeImmutable();
        $mediaAsset->setUploadedAt($occurredAt);

        $this->projectMediaAssetRepository->save($mediaAsset);
        $this->eventDispatcher->dispatch(
            new ProjectImageUploadedSymfonyEvent(
                $projectUuid,
                $mediaAsset->getOriginalFilename(),
                $occurredAt
            )
        );

        return $this->mapProjectMediaAssetToDto($mediaAsset);
    }

    public function replaceProjectMediaAsset(string $projectUuid, UploadedFile $uploadedFile): ProjectMediaAssetDto
    {
        $this->ensureProjectExists($projectUuid);
        $this->assertSupportedImageFile($uploadedFile);

        $existingAsset = $this->projectMediaAssetRepository->findCurrentByProjectUuid($projectUuid);
        if (!$existingAsset instanceof ProjectMediaAsset) {
            return $this->uploadProjectMediaAsset($projectUuid, $uploadedFile);
        }

        $extension  = $this->resolveExtension($uploadedFile);
        $storedFile = $this->projectMediaAssetStorage->replaceStoredFile(
            $existingAsset->getStoredFilename(),
            $uploadedFile,
            $this->projectMediaAssetStorage->buildStorageFilename($projectUuid, $extension)
        );

        $existingAsset->setOriginalFilename($uploadedFile->getClientOriginalName());
        $existingAsset->setStoredFilename($storedFile->storedFilename);
        $existingAsset->setMimeType($storedFile->mimeType);
        $existingAsset->setExtension($storedFile->extension);
        $existingAsset->setSizeBytes($storedFile->sizeBytes);
        $existingAsset->setWidthPixels($storedFile->widthPixels);
        $existingAsset->setHeightPixels($storedFile->heightPixels);
        $occurredAt = DateAndTimeService::getDateTimeImmutable();
        $existingAsset->setUploadedAt($occurredAt);

        $this->projectMediaAssetRepository->save($existingAsset);
        $this->eventDispatcher->dispatch(
            new ProjectImageReplacedSymfonyEvent(
                $projectUuid,
                $existingAsset->getOriginalFilename(),
                $occurredAt
            )
        );

        return $this->mapProjectMediaAssetToDto($existingAsset);
    }

    public function getCurrentProjectMediaAsset(string $projectUuid): ?ProjectMediaAssetDto
    {
        $mediaAsset = $this->projectMediaAssetRepository->findCurrentByProjectUuid($projectUuid);

        return $mediaAsset instanceof ProjectMediaAsset ? $this->mapProjectMediaAssetToDto($mediaAsset) : null;
    }

    public function deleteCurrentProjectMediaAsset(string $projectUuid): void
    {
        $mediaAsset = $this->projectMediaAssetRepository->findCurrentByProjectUuid($projectUuid);

        if (!$mediaAsset instanceof ProjectMediaAsset) {
            return;
        }

        $this->projectMediaAssetStorage->deleteStoredFile($mediaAsset->getStoredFilename());
        $this->projectMediaAssetRepository->remove($mediaAsset);
    }

    private function ensureProjectExists(string $projectUuid): void
    {
        if (!$this->projectManagementFacade->projectExists($projectUuid)) {
            throw new ValueError('Target project does not exist.');
        }

        if ($this->projectManagementFacade->getProjectByUuid($projectUuid)->cancelled) {
            throw new ValueError('Archivierte Projekte koennen keine neuen Bilder erhalten.');
        }
    }

    private function assertSupportedImageFile(UploadedFile $file): void
    {
        $extension = $this->resolveExtension($file);

        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            throw new ValueError('Only JPG and PNG files are supported for project images.');
        }
    }

    private function resolveExtension(UploadedFile $file): string
    {
        $extension = mb_strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));

        if ($extension === 'jpeg') {
            return 'jpg';
        }

        if ($extension === '') {
            throw new ValueError('Uploaded file extension could not be detected.');
        }

        return $extension;
    }

    private function mapProjectMediaAssetToDto(ProjectMediaAsset $mediaAsset): ProjectMediaAssetDto
    {
        return new ProjectMediaAssetDto(
            $mediaAsset->getUuid(),
            $mediaAsset->getProjectUuid(),
            $mediaAsset->getOriginalFilename(),
            $mediaAsset->getStoredFilename(),
            $this->projectMediaAssetStorage->resolveStoragePath($mediaAsset->getStoredFilename()),
            $mediaAsset->getMimeType(),
            $mediaAsset->getExtension(),
            $mediaAsset->getSizeBytes(),
            $mediaAsset->getWidthPixels(),
            $mediaAsset->getHeightPixels(),
            $mediaAsset->getUploadedAt()
        );
    }
}
