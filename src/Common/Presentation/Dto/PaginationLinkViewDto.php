<?php

declare(strict_types=1);

namespace App\Common\Presentation\Dto;

readonly class PaginationLinkViewDto
{
    public function __construct(
        public int    $page,
        public string $url,
        public bool   $isCurrent
    ) {
    }
}
