<?php

declare(strict_types=1);

namespace App\CsvExport\Domain\Service;

use App\CsvExport\Domain\Dto\CsvRowDto;
use RuntimeException;

readonly class CsvWriterService implements CsvWriterServiceInterface
{
    /**
     * @param list<string>    $headers
     * @param list<CsvRowDto> $rows
     */
    public function writeCsv(string $downloadFilename, array $headers, array $rows): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'track-manager-csv-');
        if ($filePath === false) {
            throw new RuntimeException('CSV-Datei konnte nicht erstellt werden.');
        }

        $handle = fopen($filePath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('CSV-Datei konnte nicht geöffnet werden.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers, ';', '"', '\\');

        foreach ($rows as $row) {
            fputcsv($handle, $row->values, ';', '"', '\\');
        }

        fclose($handle);

        return $filePath;
    }
}
