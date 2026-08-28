<?php

namespace App\Http\Controllers\Api;

use App\Domain\Doctors\Data\DoctorMatchResult;
use App\Domain\Doctors\Data\MatchDoctorsCommand;
use App\Domain\Doctors\Services\DoctorMatchingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\MatchDoctorsRequest;
use Illuminate\Http\JsonResponse;

class DoctorMatchController extends Controller
{
    public function __invoke(MatchDoctorsRequest $request, DoctorMatchingService $doctorMatchingService): JsonResponse
    {
        $command = MatchDoctorsCommand::fromRequest($request);

        $matches = $doctorMatchingService->match(
            symptoms: $command->symptoms,
            limit: $command->limit,
        );

        return response()->json([
            'data' => $matches->map(
                fn (DoctorMatchResult $result): array => $result->toArray()
            )->values()->all(),
        ]);
    }
}
