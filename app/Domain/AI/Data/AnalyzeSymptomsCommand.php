<?php

namespace App\Domain\AI\Data;

use App\Http\Requests\AnalyzeSymptomsRequest;

readonly class AnalyzeSymptomsCommand
{
    public function __construct(
        public string $symptoms,
    ) {}

    public static function fromRequest(AnalyzeSymptomsRequest $request): self
    {
        return new self(
            symptoms: $request->string('symptoms')->toString(),
        );
    }
}
