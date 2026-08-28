<?php

namespace App\Domain\AI\Contracts;

use App\Domain\AI\Data\TriageSummary;

interface TriageExtractor
{
    public function extract(string $symptoms): TriageSummary;
}
