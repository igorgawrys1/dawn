<?php

declare(strict_types=1);

namespace Dawn\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use PHPUnit\Framework\Assert as PHPUnit;

trait InteractsWithAuthentication
{
    /**
     * Log into the application as the default user.
     */
    public function login(): static
    {
        if (static::$userResolver === null) {
            throw new \RuntimeException(
                'User resolver has not been set. Define a user() method on your test case or use loginAs().'
            );
        }

        return $this->loginAs((static::$userResolver)());
    }

    /**
     * Log into the application using a given user ID or email.
     */
    public function loginAs(Authenticatable|string|int $userId, ?string $guard = null): static
    {
        $userId = $userId instanceof Authenticatable ? $userId->getAuthIdentifier() : $userId;

        return $this->visit(rtrim(route('dawn.login', ['userId' => $userId, 'guard' => $guard], $this->shouldUseAbsoluteRouteForAuthentication())));
    }

    /**
     * Log out of the application.
     */
    public function logout(?string $guard = null): static
    {
        return $this->visit(rtrim(route('dawn.logout', ['guard' => $guard], $this->shouldUseAbsoluteRouteForAuthentication()), '/'));
    }

    /**
     * Get the ID and class name of the authenticated user.
     *
     * @return array<mixed>
     */
    protected function currentUserInfo(?string $guard = null): array
    {
        $this->visit(route('dawn.user', ['guard' => $guard], $this->shouldUseAbsoluteRouteForAuthentication()));

        $decoded = json_decode(strip_tags((string) $this->page->content()), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Assert that the user is authenticated.
     */
    public function assertAuthenticated(?string $guard = null): static
    {
        $currentUrl = $this->page->url();

        PHPUnit::assertNotEmpty($this->currentUserInfo($guard), 'The user is not authenticated.');

        return $this->visit($currentUrl);
    }

    /**
     * Assert that the user is not authenticated.
     */
    public function assertGuest(?string $guard = null): static
    {
        $currentUrl = $this->page->url();

        PHPUnit::assertEmpty(
            $this->currentUserInfo($guard),
            'The user is unexpectedly authenticated.'
        );

        return $this->visit($currentUrl);
    }

    /**
     * Assert that the user is authenticated as the given user.
     */
    public function assertAuthenticatedAs(Authenticatable $user, ?string $guard = null): static
    {
        $currentUrl = $this->page->url();

        $expected = [
            'id' => $user->getAuthIdentifier(),
            'className' => get_class($user),
        ];

        PHPUnit::assertSame(
            $expected,
            $this->currentUserInfo($guard),
            'The currently authenticated user is not who was expected.'
        );

        return $this->visit($currentUrl);
    }

    /**
     * Determine whether authentication routes should be generated as absolute URLs.
     */
    private function shouldUseAbsoluteRouteForAuthentication(): bool
    {
        return config('dawn.domain') !== null;
    }
}
