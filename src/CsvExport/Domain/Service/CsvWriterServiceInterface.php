<?php

declare(strict_types=1);

namespace App\CsvExport\Domain\Service;

use App\CsvExport\Domain\Dto\CsvRowDto;

interface CsvWriterServiceInterface
{
    /**
     * @param list<string>    $headers
     * @param list<CsvRowDto> $rows
     */
    public function writeCsv(string $downloadFilename, array $headers, array $rows): string;
}
