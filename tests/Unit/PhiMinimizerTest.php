<?php

use App\Domain\Compliance\Services\PhiMinimizer;

test('it redacts direct identifiers before external AI use', function () {
    $minimizer = new PhiMinimizer;

    $result = $minimizer->forExternalAi(
        'Call me at +1 (555) 123-4567 or email jane.doe@example.com. DOB 03/15/1990. MRN: ABC12345. Migraine for 4 days.',
    );

    expect($result)
        ->not->toContain('jane.doe@example.com')
        ->not->toContain('555')
        ->not->toContain('03/15/1990')
        ->not->toContain('ABC12345')
        ->toContain('Migraine for 4 days')
        ->toContain('[REDACTED_EMAIL]')
        ->toContain('[REDACTED_PHONE]')
        ->toContain('[REDACTED_DATE]')
        ->toContain('[REDACTED_ID]');
});
