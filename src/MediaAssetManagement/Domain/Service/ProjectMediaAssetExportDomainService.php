<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Domain\Service;

use App\MediaAssetManagement\Facade\Dto\ExportedProjectMediaAssetDto;
use App\MediaAssetManagement\Infrastructure\Repository\ProjectMediaAssetRepositoryInterface;
use App\MediaAssetManagement\Infrastructure\Storage\ProjectMediaAssetStorageInterface;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use GdImage;
use ValueError;

readonly class ProjectMediaAssetExportDomainService implements ProjectMediaAssetExportDomainServiceInterface
{
    private const SPOTIFY_EXPORT_SIZE = 3000;

    public function __construct(
        private ProjectMediaAssetRepositoryInterface $projectMediaAssetRepository,
        private ProjectMediaAssetStorageInterface $projectMediaAssetStorage,
        private ProjectManagementFacadeInterface $projectManagementFacade
    ) {
    }

    public function exportProjectMediaAsset(string $projectUuid, string $targetFormat): ExportedProjectMediaAssetDto
    {
        $targetFormat = mb_strtolower(trim($targetFormat));
        if (!in_array($targetFormat, ['jpg', 'png'], true)) {
            throw new ValueError('Only JPG and PNG exports are supported for project images.');
        }

        $project = $this->projectManagementFacade->getProjectByUuid($projectUuid);
        $mediaAsset = $this->projectMediaAssetRepository->findCurrentByProjectUuid($projectUuid);

        if ($mediaAsset === null) {
            throw new ValueError('Project has no image to export.');
        }

        $sourcePath = $this->projectMediaAssetStorage->resolveStoragePath($mediaAsset->getStoredFilename());
        $temporaryFilePath = sys_get_temp_dir() . '/' . uniqid('project-media-export-', true) . '.' . $targetFormat;

        $sourceImage = $this->createImageResource($sourcePath, $mediaAsset->getExtension());
        $exportImage = $this->createSquareSpotifyImage($sourceImage, $mediaAsset->getWidthPixels(), $mediaAsset->getHeightPixels(), $targetFormat);

        if ($targetFormat === 'png') {
            imagepng($exportImage, $temporaryFilePath);
        } else {
            imagejpeg($exportImage, $temporaryFilePath, 92);
        }

        imagedestroy($sourceImage);
        imagedestroy($exportImage);

        $safeTitle = preg_replace('/[^A-Za-z0-9_\-]+/', '_', trim($project->title)) ?? trim($project->title);
        $safeTitle = trim($safeTitle, '_');
        if ($safeTitle === '') {
            $safeTitle = 'project_cover';
        }

        return new ExportedProjectMediaAssetDto(
            $temporaryFilePath,
            sprintf('%s_Cover_3000x3000.%s', $safeTitle, $targetFormat),
            $targetFormat === 'png' ? 'image/png' : 'image/jpeg'
        );
    }

    private function createImageResource(string $sourcePath, string $extension): GdImage
    {
        $image = match ($extension) {
            'png' => imagecreatefrompng($sourcePath),
            'jpg', 'jpeg' => imagecreatefromjpeg($sourcePath),
            default => throw new ValueError('Stored project image format is not supported for export.'),
        };

        if (!$image instanceof GdImage) {
            throw new ValueError('Stored project image could not be loaded for export.');
        }

        return $image;
    }

    private function createSquareSpotifyImage(GdImage $sourceImage, int $sourceWidth, int $sourceHeight, string $targetFormat): GdImage
    {
        $destinationImage = imagecreatetruecolor(self::SPOTIFY_EXPORT_SIZE, self::SPOTIFY_EXPORT_SIZE);

        if ($targetFormat === 'png') {
            imagealphablending($destinationImage, false);
            imagesavealpha($destinationImage, true);
            $transparent = imagecolorallocatealpha($destinationImage, 0, 0, 0, 127);
            if ($transparent === false) {
                throw new ValueError('PNG export background could not be prepared.');
            }

            imagefill($destinationImage, 0, 0, $transparent);
        } else {
            $background = imagecolorallocate($destinationImage, 255, 255, 255);
            if ($background === false) {
                throw new ValueError('JPG export background could not be prepared.');
            }

            imagefill($destinationImage, 0, 0, $background);
        }

        $cropSize = min($sourceWidth, $sourceHeight);
        $sourceX = (int) floor(($sourceWidth - $cropSize) / 2);
        $sourceY = (int) floor(($sourceHeight - $cropSize) / 2);

        imagecopyresampled(
            $destinationImage,
            $sourceImage,
            0,
            0,
            $sourceX,
            $sourceY,
            self::SPOTIFY_EXPORT_SIZE,
            self::SPOTIFY_EXPORT_SIZE,
            $cropSize,
            $cropSize
        );

        return $destinationImage;
    }
}
