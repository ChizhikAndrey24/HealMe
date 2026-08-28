<?php

namespace App\Http\Controllers\Web;

use App\Domain\Appointments\Data\BookedAppointmentSummary;
use App\Domain\Appointments\Services\PatientAppointmentQueryService;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PatientAppointmentsController extends Controller
{
    public function __invoke(
        Request $request,
        PatientAppointmentQueryService $patientAppointmentQueryService,
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user !== null
                && $user->hasPermission(Permission::AppointmentsViewOwn)
                && $user->patientProfile !== null,
            Response::HTTP_FORBIDDEN,
        );

        $bookings = $patientAppointmentQueryService->listForPatient($user->patientProfile);

        return response()->json([
            'data' => $bookings
                ->map(static fn (BookedAppointmentSummary $booking): array => $booking->toArray())
                ->all(),
        ]);
    }
}
