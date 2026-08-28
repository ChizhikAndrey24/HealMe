<?php

namespace App\Http\Controllers\Web;

use App\Domain\Doctors\Services\DoctorDirectoryService;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DoctorDirectoryController extends Controller
{
    public function __invoke(Request $request, DoctorDirectoryService $directoryService): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless(
            $user !== null
                && $user->hasPermission(Permission::PatientCardManageAccess)
                && $user->patientProfile !== null,
            Response::HTTP_FORBIDDEN,
        );

        $page = $directoryService->listForPatient(
            patientProfile: $user->patientProfile,
            name: $request->query('name'),
            specialization: $request->query('specialization'),
            page: max((int) $request->integer('page', 1), 1),
            perPage: (int) $request->integer('per_page', 12),
        );

        return response()->json([
            'data' => $page->items(),
            'meta' => $page->meta(),
            'filters' => [
                'specializations' => $page->specializations,
            ],
        ]);
    }
}
