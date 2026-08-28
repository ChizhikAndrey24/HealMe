<?php

namespace App\Http\Controllers\Web;

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Patients\Data\AccessiblePatientSummary;
use App\Domain\Patients\Data\PatientCardNoteSummary;
use App\Domain\Patients\Exceptions\PatientCardAccessException;
use App\Domain\Patients\Models\PatientProfile;
use App\Domain\Patients\Services\DoctorPatientAccessService;
use App\Domain\Patients\Services\PatientCardNoteService;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Data\ApiErrorData;
use App\Http\Requests\StorePatientCardNoteRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DoctorPatientCardController extends Controller
{
    public function patients(
        Request $request,
        DoctorPatientAccessService $accessService,
    ): JsonResponse {
        $doctor = $this->doctor($request, Permission::PatientCardView);

        $patients = $accessService->listApprovedPatientsForDoctor($doctor);

        return response()->json([
            'data' => $patients
                ->map(static fn (AccessiblePatientSummary $patient): array => $patient->toArray())
                ->all(),
        ]);
    }

    public function notes(
        Request $request,
        int $patientProfileId,
        PatientCardNoteService $noteService,
    ): JsonResponse {
        $doctor = $this->doctor($request, Permission::PatientCardView);
        $patientProfile = PatientProfile::query()->findOrFail($patientProfileId);

        try {
            $notes = $noteService->listForDoctor($doctor, $patientProfile);
        } catch (PatientCardAccessException $exception) {
            return (new ApiErrorData($exception->getMessage()))
                ->toJsonResponse(Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => $notes
                ->map(static fn (PatientCardNoteSummary $note): array => $note->toArray())
                ->all(),
        ]);
    }

    public function storeNote(
        StorePatientCardNoteRequest $request,
        int $patientProfileId,
        PatientCardNoteService $noteService,
    ): JsonResponse {
        $doctor = $this->doctor($request, Permission::PatientCardWrite);
        $patientProfile = PatientProfile::query()->findOrFail($patientProfileId);

        try {
            $note = $noteService->addDoctorNote(
                doctor: $doctor,
                patientProfile: $patientProfile,
                body: (string) $request->validated('body'),
            );
        } catch (PatientCardAccessException $exception) {
            return (new ApiErrorData($exception->getMessage()))
                ->toJsonResponse(Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => PatientCardNoteSummary::fromModel($note)->toArray(),
        ], Response::HTTP_CREATED);
    }

    private function doctor(Request $request, Permission $permission): Doctor
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless(
            $user !== null
                && $user->hasPermission($permission)
                && $user->doctorProfile !== null,
            Response::HTTP_FORBIDDEN,
        );

        return $user->doctorProfile;
    }
}
