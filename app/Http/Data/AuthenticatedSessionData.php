<?php

namespace App\Http\Data;

use App\Models\User;

readonly class AuthenticatedSessionData
{
    public function __construct(
        public bool $authenticated,
        public ?UserSessionData $user,
    ) {}

    public static function guest(): self
    {
        return new self(authenticated: false, user: null);
    }

    public static function fromUser(User $user): self
    {
        return new self(
            authenticated: true,
            user: UserSessionData::fromUser($user),
        );
    }

    /**
     * @return array{
     *     authenticated: bool,
     *     user: array<string, mixed>|null
     * }
     */
    public function toArray(): array
    {
        return [
            'authenticated' => $this->authenticated,
            'user' => $this->user?->toArray(),
        ];
    }
}
