<?php

declare(strict_types=1);

namespace Dawn\Concerns;

use Dawn\Exceptions\UnsupportedDuskMethod;

trait InteractsWithCookies
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function cookie(string $name, ?string $value = null, \DateTimeInterface|int|null $expiry = null, array $options = []): mixed
    {
        throw UnsupportedDuskMethod::make('cookie');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function plainCookie(string $name, ?string $value = null, \DateTimeInterface|int|null $expiry = null, array $options = []): mixed
    {
        throw UnsupportedDuskMethod::make('plainCookie');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function addCookie(string $name, string $value, \DateTimeInterface|int|null $expiry = null, array $options = [], bool $encrypt = true): static
    {
        throw UnsupportedDuskMethod::make('addCookie');
    }

    public function deleteCookie(string $name): static
    {
        throw UnsupportedDuskMethod::make('deleteCookie');
    }
}
