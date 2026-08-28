<?php

namespace App\Domain\Users\Data;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

readonly class GoogleUserSyncData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $googleId,
        public ?string $avatar,
    ) {}

    public static function fromIdentity(GoogleIdentityData $identity): self
    {
        return new self(
            name: $identity->name,
            email: $identity->email,
            googleId: $identity->googleId,
            avatar: $identity->avatar,
        );
    }

    /**
     * @return array{
     *     name: string,
     *     email: string,
     *     google_id: string,
     *     avatar: string|null,
     *     password: string,
     *     email_verified_at: Carbon
     * }
     */
    public function toFillAttributes(?User $existingUser = null): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'google_id' => $this->googleId,
            'avatar' => $this->avatar,
            'password' => $existingUser === null
                ? Hash::make(Str::uuid()->toString())
                : $existingUser->password,
            'email_verified_at' => now(),
        ];
    }
}
