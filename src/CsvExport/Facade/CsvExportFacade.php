<?php

declare(strict_types=1);

namespace App\CsvExport\Facade;

use App\CsvExport\Domain\Dto\CsvRowDto;
use App\CsvExport\Domain\Service\CsvWriterServiceInterface;
use App\CsvExport\Facade\Dto\CsvDownloadDto;
use App\CsvExport\Facade\Dto\ProjectCsvExportRowDto;
use App\CsvExport\Facade\Dto\TrackCsvExportRowDto;
use App\ProjectManagement\Facade\Dto\ProjectListFilterInputDto;
use App\ProjectManagement\Facade\Dto\TrackProjectMembershipDto;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\TrackManagement\Facade\Dto\TrackListFilterInputDto;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;

readonly class CsvExportFacade implements CsvExportFacadeInterface
{
    public function __construct(
        private TrackManagementFacadeInterface   $trackManagementFacade,
        private ProjectManagementFacadeInterface $projectManagementFacade,
        private CsvWriterServiceInterface        $csvWriterService
    ) {
    }

    public function exportTracks(TrackListFilterInputDto $filter): CsvDownloadDto
    {
        $rows = [];
        foreach ($this->trackManagementFacade->getTracksByFilter($filter) as $track) {
            $projectTitles = array_map(
                static fn (TrackProjectMembershipDto $membership): string => $membership->projectTitle,
                $this->projectManagementFacade->getProjectsByTrackUuid($track->uuid)
            );

            $rows[] = new TrackCsvExportRowDto(
                (string) $track->trackNumber,
                $track->title,
                $track->beatName,
                $track->publishingName ?? '',
                $this->formatBpms($track->bpms),
                implode(', ', $track->musicalKeys),
                $this->formatStatus($track->status),
                $this->formatBoolean($track->published),
                $this->formatBoolean($track->cancelled),
                sprintf('%d%%', $track->progress),
                implode(', ', $projectTitles)
            );
        }

        $filePath = $this->csvWriterService->writeCsv(
            'tracks-export.csv',
            ['ID', 'Titel', 'Beat Name', 'Publishing Name', 'BPM', 'Key', 'Status', 'Published', 'Archiviert', 'Progress', 'Projekte'],
            array_map(
                static fn (TrackCsvExportRowDto $row): CsvRowDto => new CsvRowDto([
                    $row->id,
                    $row->title,
                    $row->beatName,
                    $row->publishingName,
                    $row->bpm,
                    $row->musicalKey,
                    $row->status,
                    $row->published,
                    $row->cancelled,
                    $row->progress,
                    $row->projects,
                ]),
                $rows
            )
        );

        return new CsvDownloadDto($filePath, 'text/csv; charset=UTF-8', 'tracks-export.csv');
    }

    public function exportProjects(ProjectListFilterInputDto $filter): CsvDownloadDto
    {
        $rows = [];
        foreach ($this->projectManagementFacade->getProjectsByFilter($filter) as $project) {
            $trackLabels = [];
            foreach ($this->projectManagementFacade->getTrackAssignmentsByProjectUuid($project->uuid) as $assignment) {
                $track         = $this->trackManagementFacade->getTrackByUuid($assignment->trackUuid);
                $trackLabels[] = $track->publishingName ?? $track->title;
            }

            $rows[] = new ProjectCsvExportRowDto(
                $project->uuid,
                $project->title,
                $project->categoryName,
                implode(', ', $project->artists),
                $this->formatBoolean($project->published),
                $this->formatBoolean($project->cancelled),
                (string) $project->trackCount,
                implode(', ', $trackLabels)
            );
        }

        $filePath = $this->csvWriterService->writeCsv(
            'projects-export.csv',
            ['ID', 'Titel', 'Kategorie', 'Interpreten', 'Published', 'Archiviert', 'Anzahl Tracks', 'Tracks'],
            array_map(
                static fn (ProjectCsvExportRowDto $row): CsvRowDto => new CsvRowDto([
                    $row->id,
                    $row->title,
                    $row->category,
                    $row->artists,
                    $row->published,
                    $row->cancelled,
                    $row->trackCount,
                    $row->tracks,
                ]),
                $rows
            )
        );

        return new CsvDownloadDto($filePath, 'text/csv; charset=UTF-8', 'projects-export.csv');
    }

    /**
     * @param list<float> $bpms
     */
    private function formatBpms(array $bpms): string
    {
        return implode(', ', array_map($this->formatBpm(...), $bpms));
    }

    private function formatBpm(float $bpm): string
    {
        $formattedBpm = number_format($bpm, 3, '.', '');
        $formattedBpm = rtrim($formattedBpm, '0');

        return rtrim($formattedBpm, '.');
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'new'         => 'New',
            'in_progress' => 'In Progress',
            'done'        => 'Done',
            default       => $status,
        };
    }

    private function formatBoolean(bool $value): string
    {
        return $value ? 'ja' : 'nein';
    }
}
