<?php

declare(strict_types=1);

use App\TrackManagement\Domain\Dto\TrackNamingInputDto;
use App\TrackManagement\Domain\Service\TrackNamingDomainService;

describe('TrackNamingDomainService', function (): void {
    it('builds a suggested title from track number, beat name, bpms and musical keys', function (): void {
        $service = new TrackNamingDomainService();

        $result = $service->buildSuggestedTitle(
            new TrackNamingInputDto(21, 'Tory Lanez Type Beat', [120, 180], ['a min', 'C# MAJ'])
        );

        expect($result)->toBe('21_Tory_Lanez_Type_Beat_120BPM_180BPM_Amin_C#maj');
    });

    it('falls back to a default beat name when the beat name is empty', function (): void {
        $service = new TrackNamingDomainService();

        expect($service->normalizeBeatName('   '))->toBe('UntitledBeat');
    });

    it('canonicalizes supported musical keys', function (): void {
        $service = new TrackNamingDomainService();

        expect($service->normalizeMusicalKeys([' c# MAJ ', 'A Mol']))->toBe('C#maj_Amin');
    });
});
