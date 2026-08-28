<?php

namespace App\Http\Controllers\Api;

use App\Domain\AI\Data\AnalyzeSymptomsCommand;
use App\Domain\AI\Services\TriageAnalysisService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyzeSymptomsRequest;
use Illuminate\Http\JsonResponse;

class TriageAnalysisController extends Controller
{
    public function __invoke(AnalyzeSymptomsRequest $request, TriageAnalysisService $triageAnalysisService): JsonResponse
    {
        $command = AnalyzeSymptomsCommand::fromRequest($request);
        $summary = $triageAnalysisService->analyze($command->symptoms);

        return response()->json([
            'data' => $summary->toArray(),
        ]);
    }
}
