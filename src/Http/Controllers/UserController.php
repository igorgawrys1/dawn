<?php

declare(strict_types=1);

namespace Dawn\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UserController
{
    /**
     * Get the ID and class name of the authenticated user.
     *
     * @return array<string, mixed>
     */
    public function user(?string $guard = null): array
    {
        $user = Auth::guard($guard)->user();

        if ($user === null) {
            return [];
        }

        return [
            'id' => $user->getAuthIdentifier(),
            'className' => get_class($user),
        ];
    }

    /**
     * Log the given user into the application.
     */
    public function login(string $userId, ?string $guard = null): void
    {
        $guard = $guard ?: $this->defaultGuard();

        $guardInstance = Auth::guard($guard);

        if (! $guardInstance instanceof StatefulGuard) {
            abort(500, "Guard [{$guard}] does not support session login.");
        }

        $providerName = config("auth.guards.{$guard}.provider");

        $provider = Auth::createUserProvider(is_string($providerName) ? $providerName : '');

        if ($provider === null) {
            abort(500, "Unable to resolve the user provider for guard [{$guard}].");
        }

        $user = str_contains($userId, '@')
            ? $provider->retrieveByCredentials(['email' => $userId])
            : $provider->retrieveById($userId);

        if ($user === null) {
            abort(404, "User [{$userId}] not found.");
        }

        $guardInstance->login($user);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(?string $guard = null): void
    {
        $guard = $guard ?: $this->defaultGuard();

        Auth::guard($guard)->logout();

        Session::forget('password_hash_'.$guard);
    }

    protected function defaultGuard(): string
    {
        $guard = config('auth.defaults.guard');

        return is_string($guard) ? $guard : 'web';
    }
}
