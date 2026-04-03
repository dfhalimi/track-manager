<?php

declare(strict_types=1);

use App\CsvExport\Facade\CsvExportFacadeInterface;
use App\CsvExport\Facade\Dto\CsvDownloadDto;
use App\CsvExport\Presentation\Controller\CsvExportController;
use App\ProjectManagement\Facade\Dto\ProjectListFilterInputDto;
use App\TrackManagement\Facade\Dto\TrackListFilterInputDto;
use Symfony\Component\HttpFoundation\Request;

describe('CsvExportController', function (): void {
    it('passes track overview filters into the csv export facade', function (): void {
        $facade     = new RecordingCsvExportFacade();
        $controller = new CsvExportController($facade);

        $response = $controller->exportTracksAction(
            new Request([
                'q'             => 'alpha',
                'status'        => 'in_progress',
                'cancelled'     => 'active',
                'sortBy'        => 'status',
                'sortDirection' => 'ASC',
            ])
        );

        expect($facade->trackFilter?->searchQuery)->toBe('alpha');
        expect($facade->trackFilter?->statusFilter)->toBe('in_progress');
        expect($facade->trackFilter?->cancelledFilter)->toBe('active');
        expect($facade->trackFilter?->sortBy)->toBe('status');
        expect($facade->trackFilter?->sortDirection)->toBe('ASC');
        expect($response->headers->get('content-type'))->toContain('text/csv');

        cleanupDownload($facade->lastDownload);
    });

    it('passes project overview filters into the csv export facade', function (): void {
        $facade     = new RecordingCsvExportFacade();
        $controller = new CsvExportController($facade);

        $response = $controller->exportProjectsAction(
            new Request([
                'q'             => 'spring',
                'category'      => 'EP',
                'cancelled'     => 'cancelled',
                'sortBy'        => 'title',
                'sortDirection' => 'DESC',
            ])
        );

        expect($facade->projectFilter?->searchQuery)->toBe('spring');
        expect($facade->projectFilter?->categoryFilter)->toBe('EP');
        expect($facade->projectFilter?->cancelledFilter)->toBe('cancelled');
        expect($facade->projectFilter?->sortBy)->toBe('title');
        expect($facade->projectFilter?->sortDirection)->toBe('DESC');
        expect($response->headers->get('content-type'))->toContain('text/csv');

        cleanupDownload($facade->lastDownload);
    });
});

function cleanupDownload(?CsvDownloadDto $download): void
{
    if ($download instanceof CsvDownloadDto && is_file($download->filePath)) {
        unlink($download->filePath);
    }
}

final class RecordingCsvExportFacade implements CsvExportFacadeInterface
{
    public ?TrackListFilterInputDto $trackFilter     = null;
    public ?ProjectListFilterInputDto $projectFilter = null;
    public ?CsvDownloadDto $lastDownload             = null;

    public function exportTracks(TrackListFilterInputDto $filter): CsvDownloadDto
    {
        $this->trackFilter = $filter;

        return $this->lastDownload = $this->createDownload('tracks-export.csv');
    }

    public function exportProjects(ProjectListFilterInputDto $filter): CsvDownloadDto
    {
        $this->projectFilter = $filter;

        return $this->lastDownload = $this->createDownload('projects-export.csv');
    }

    private function createDownload(string $filename): CsvDownloadDto
    {
        $filePath = tempnam(sys_get_temp_dir(), 'csv-export-controller-');
        if ($filePath === false) {
            throw new RuntimeException('Temporary CSV file could not be created.');
        }

        file_put_contents($filePath, 'csv');

        return new CsvDownloadDto($filePath, 'text/csv; charset=UTF-8', $filename);
    }
}
