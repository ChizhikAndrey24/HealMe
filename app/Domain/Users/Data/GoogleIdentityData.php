<?php

namespace App\Domain\Users\Data;

use Laravel\Socialite\Two\User as SocialiteUser;

readonly class GoogleIdentityData
{
    public function __construct(
        public string $googleId,
        public string $email,
        public string $name,
        public ?string $avatar,
    ) {}

    public static function fromSocialite(SocialiteUser $googleUser): self
    {
        $name = $googleUser->getName() ?: $googleUser->getNickname() ?: 'HealMe User';
        $avatar = $googleUser->getAvatar();

        if (! is_string($avatar) || $avatar === '') {
            $picture = $googleUser->user['picture'] ?? null;
            $avatar = is_string($picture) && $picture !== '' ? $picture : null;
        }

        return new self(
            googleId: (string) $googleUser->getId(),
            email: (string) $googleUser->getEmail(),
            name: $name,
            avatar: $avatar,
        );
    }
}
