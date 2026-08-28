<?php

namespace App\Http\Controllers\Web;

use App\Domain\Appointments\Data\BookAppointmentCommand;
use App\Domain\Appointments\Data\BookedAppointmentSummary;
use App\Domain\Appointments\Exceptions\AppointmentBookingException;
use App\Domain\Appointments\Services\AppointmentBookingService;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Data\ApiErrorData;
use App\Http\Requests\BookAppointmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AppointmentBookingController extends Controller
{
    public function __invoke(
        BookAppointmentRequest $request,
        AppointmentBookingService $appointmentBookingService,
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user !== null
                && $user->hasPermission(Permission::AppointmentsBook)
                && $user->patientProfile !== null,
            Response::HTTP_FORBIDDEN,
        );

        try {
            $appointment = $appointmentBookingService->bookPendingAppointment(
                patientProfile: $user->patientProfile,
                command: BookAppointmentCommand::fromRequest($request),
            );
        } catch (AppointmentBookingException $exception) {
            return (new ApiErrorData($exception->getMessage()))
                ->toJsonResponse(Response::HTTP_CONFLICT);
        }

        return response()->json([
            'data' => BookedAppointmentSummary::fromAppointment($appointment)->toArray(),
        ], Response::HTTP_CREATED);
    }
}
