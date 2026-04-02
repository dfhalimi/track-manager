<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Dto;

readonly class TrackFormViewDto
{
    /**
     * @param list<int>    $bpms
     * @param list<string> $musicalKeys
     * @param list<string> $musicalKeyOptions
     */
    public function __construct(
        public ?string $trackUuid,
        public int     $trackNumber,
        public string  $beatName,
        public string  $title,
        public ?string $publishingName,
        public array   $bpms,
        public array   $musicalKeys,
        public array   $musicalKeyOptions,
        public ?string $notes,
        public ?string $isrc,
        public string  $formAction,
        public string  $cancelUrl,
        public string  $submitLabel,
        public string  $suggestedTitle,
        public bool    $isEditMode
    ) {
    }
}
