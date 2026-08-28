<?php

namespace App\Http\Controllers\Web;

use App\Domain\Appointments\Data\BookedAppointmentSummary;
use App\Domain\Appointments\Exceptions\AppointmentBookingException;
use App\Domain\Appointments\Models\Appointment;
use App\Domain\Appointments\Services\DoctorAppointmentService;
use App\Domain\Doctors\Models\Doctor;
use App\Enums\AppointmentStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Data\ApiErrorData;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DoctorAppointmentController extends Controller
{
    public function index(Request $request, DoctorAppointmentService $service): JsonResponse
    {
        $doctor = $this->doctor($request);
        $status = AppointmentStatus::tryFrom((string) $request->query('status', ''));

        $appointments = $service->listForDoctor($doctor, $status);

        return response()->json([
            'data' => $appointments
                ->map(static fn (BookedAppointmentSummary $item): array => $item->toArray())
                ->all(),
        ]);
    }

    public function approve(
        Request $request,
        int $appointmentId,
        DoctorAppointmentService $service,
    ): JsonResponse {
        $doctor = $this->doctor($request);
        $appointment = Appointment::query()->findOrFail($appointmentId);

        try {
            $appointment = $service->approve($doctor, $appointment);
        } catch (AppointmentBookingException $exception) {
            return (new ApiErrorData($exception->getMessage()))
                ->toJsonResponse(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => BookedAppointmentSummary::fromAppointment($appointment)->toArray(),
        ]);
    }

    public function reject(
        Request $request,
        int $appointmentId,
        DoctorAppointmentService $service,
    ): JsonResponse {
        $doctor = $this->doctor($request);
        $appointment = Appointment::query()->findOrFail($appointmentId);

        try {
            $appointment = $service->reject($doctor, $appointment);
        } catch (AppointmentBookingException $exception) {
            return (new ApiErrorData($exception->getMessage()))
                ->toJsonResponse(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => BookedAppointmentSummary::fromAppointment($appointment)->toArray(),
        ]);
    }

    private function doctor(Request $request): Doctor
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless(
            $user !== null
                && $user->hasPermission(Permission::AppointmentsManage)
                && $user->doctorProfile !== null,
            Response::HTTP_FORBIDDEN,
        );

        return $user->doctorProfile;
    }
}
