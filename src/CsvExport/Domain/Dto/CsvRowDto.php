<?php

declare(strict_types=1);

namespace App\CsvExport\Domain\Dto;

readonly class CsvRowDto
{
    /**
     * @param list<string> $values
     */
    public function __construct(
        public array $values
    ) {
    }
}
