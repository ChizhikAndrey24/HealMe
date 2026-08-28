<?php

namespace App\Domain\Doctors\Data;

use App\Http\Requests\MatchDoctorsRequest;

readonly class MatchDoctorsCommand
{
    public function __construct(
        public string $symptoms,
        public int $limit = 5,
    ) {}

    public static function fromRequest(MatchDoctorsRequest $request): self
    {
        return new self(
            symptoms: $request->string('symptoms')->toString(),
            limit: $request->integer('limit', 5),
        );
    }
}
