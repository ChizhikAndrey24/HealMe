<?php

namespace App\Http\Controllers\Web\Auth;

use App\Domain\Users\Data\GoogleIdentityData;
use App\Domain\Users\Services\GoogleAuthenticationService;
use App\Domain\Users\Services\GoogleTokenService;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(string $role): RedirectResponse
    {
        $userRole = UserRole::tryFrom($role);

        abort_if($userRole === null || $userRole === UserRole::SuperAdmin, 404);

        session(['auth_role' => $userRole->value]);

        $driver = Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email']);

        if ($userRole === UserRole::Doctor) {
            // Offline consent is optional for doctors; Meet links use the shared app token.
            $driver = $driver->with([
                'access_type' => 'offline',
                'prompt' => 'select_account',
            ]);
        }

        return $driver->redirect();
    }

    public function callback(
        GoogleAuthenticationService $googleAuthenticationService,
        GoogleTokenService $googleTokenService,
    ): RedirectResponse {
        $intendedRole = UserRole::tryFrom((string) session('auth_role', UserRole::Patient->value)) ?? UserRole::Patient;
        /** @var SocialiteUser $googleUser */
        $googleUser = Socialite::driver('google')->user();

        $user = $googleAuthenticationService->authenticate(
            identity: GoogleIdentityData::fromSocialite($googleUser),
            intendedRole: $intendedRole,
        );

        if ($user === null) {
            return redirect('/?auth_error=role_mismatch');
        }

        $googleTokenService->storeFromSocialite($user, $googleUser);

        return redirect('/');
    }
}
