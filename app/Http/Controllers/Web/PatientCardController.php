<?php

namespace App\Http\Controllers\Web;

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Patients\Data\DoctorPatientAccessSummary;
use App\Domain\Patients\Data\PatientCardNoteSummary;
use App\Domain\Patients\Exceptions\PatientCardAccessException;
use App\Domain\Patients\Models\PatientProfile;
use App\Domain\Patients\Services\DoctorPatientAccessService;
use App\Domain\Patients\Services\PatientCardNoteService;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Data\ApiErrorData;
use App\Http\Requests\ManageDoctorPatientAccessRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PatientCardController extends Controller
{
    public function notes(Request $request, PatientCardNoteService $noteService): JsonResponse
    {
        $profile = $this->patientProfile($request);

        $notes = $noteService->listForPatient($profile);

        return response()->json([
            'data' => $notes
                ->map(static fn (PatientCardNoteSummary $note): array => $note->toArray())
                ->all(),
        ]);
    }

    public function access(
        Request $request,
        DoctorPatientAccessService $accessService,
    ): JsonResponse {
        $profile = $this->patientProfile($request, Permission::PatientCardManageAccess);

        $grants = $accessService->listForPatient($profile);

        return response()->json([
            'data' => [
                'grants' => $grants
                    ->map(static fn (DoctorPatientAccessSummary $grant): array => $grant->toArray())
                    ->all(),
                'candidates' => $accessService->listApprovingCandidates()->all(),
            ],
        ]);
    }

    public function approveAccess(
        ManageDoctorPatientAccessRequest $request,
        DoctorPatientAccessService $accessService,
    ): JsonResponse {
        $profile = $this->patientProfile($request, Permission::PatientCardManageAccess);
        $doctor = Doctor::query()->findOrFail((int) $request->validated('doctor_id'));

        try {
            $access = $accessService->approveByPatient($profile, $doctor);
        } catch (PatientCardAccessException $exception) {
            return (new ApiErrorData($exception->getMessage()))
                ->toJsonResponse(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => DoctorPatientAccessSummary::fromModel($access)->toArray(),
        ]);
    }

    public function revokeAccess(
        ManageDoctorPatientAccessRequest $request,
        DoctorPatientAccessService $accessService,
    ): JsonResponse {
        $profile = $this->patientProfile($request, Permission::PatientCardManageAccess);
        $doctor = Doctor::query()->findOrFail((int) $request->validated('doctor_id'));

        try {
            $access = $accessService->revokeByPatient($profile, $doctor);
        } catch (PatientCardAccessException $exception) {
            return (new ApiErrorData($exception->getMessage()))
                ->toJsonResponse(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => DoctorPatientAccessSummary::fromModel($access)->toArray(),
        ]);
    }

    private function patientProfile(Request $request, ?Permission $permission = null): PatientProfile
    {
        /** @var User|null $user */
        $user = $request->user();
        $required = $permission ?? Permission::PatientCardViewOwn;

        abort_unless(
            $user !== null
                && $user->hasPermission($required)
                && $user->patientProfile !== null,
            Response::HTTP_FORBIDDEN,
        );

        return $user->patientProfile;
    }
}
