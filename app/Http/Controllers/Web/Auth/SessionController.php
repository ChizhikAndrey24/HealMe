<?php

namespace App\Http\Controllers\Web\Auth;

use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Enums\HipaaAuditAction;
use App\Http\Controllers\Controller;
use App\Http\Data\AuthenticatedSessionData;
use App\Http\Requests\PasswordLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SessionController extends Controller
{
    public function __construct(
        private HipaaAuditLogger $auditLogger,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $session = $user === null
            ? AuthenticatedSessionData::guest()
            : AuthenticatedSessionData::fromUser($user);

        return response()->json([
            'data' => $session->toArray(),
        ]);
    }

    public function avatar(Request $request): SymfonyResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        $avatarUrl = $user?->avatar;

        abort_if(! is_string($avatarUrl) || $avatarUrl === '', 404);
        abort_unless($this->isAllowedAvatarHost($avatarUrl), 404);

        $upstream = Http::timeout(5)
            ->withHeaders([
                'Accept' => 'image/*',
                'User-Agent' => 'HealMeAvatarProxy/1.0',
            ])
            ->get($avatarUrl);

        abort_unless($upstream->successful(), 404);

        $contentType = $upstream->header('Content-Type') ?: 'image/jpeg';

        return response($upstream->body(), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function login(PasswordLoginRequest $request): JsonResponse
    {
        $email = (string) $request->validated('email');
        $password = (string) $request->validated('password');

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            $this->auditLogger->record(
                action: HipaaAuditAction::AuthLoginFailed,
                outcome: 'failure',
                metadata: ['method' => 'password', 'reason' => 'invalid_credentials'],
            );

            return response()->json([
                'message' => 'Invalid credentials.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $this->auditLogger->record(
            action: HipaaAuditAction::AuthLogin,
            actor: $user,
            metadata: [
                'method' => 'password',
                'role' => $user->primaryRole()?->value,
            ],
        );

        return response()->json([
            'data' => AuthenticatedSessionData::fromUser($user)->toArray(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user !== null) {
            $this->auditLogger->record(
                action: HipaaAuditAction::AuthLogout,
                actor: $user,
                metadata: ['method' => 'session'],
            );
        }

        return response()->json([
            'data' => AuthenticatedSessionData::guest()->toArray(),
        ]);
    }

    private function isAllowedAvatarHost(string $url): bool
    {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'https' || ! is_string($parts['host'] ?? null)) {
            return false;
        }

        $host = strtolower($parts['host']);

        return $host === 'lh3.googleusercontent.com'
            || str_ends_with($host, '.googleusercontent.com')
            || $host === 'google.com'
            || str_ends_with($host, '.google.com');
    }
}
