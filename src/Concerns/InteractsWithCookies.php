<?php

declare(strict_types=1);

namespace Dawn\Concerns;

use DateTimeInterface;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Crypt;

trait InteractsWithCookies
{
    /**
     * Get or set an encrypted cookie's value.
     *
     * @param  array<string, mixed>  $options
     */
    public function cookie(string $name, ?string $value = null, DateTimeInterface|int|null $expiry = null, array $options = []): static|string|null
    {
        if ($value !== null) {
            return $this->addCookie($name, $value, $expiry, $options);
        }

        $cookie = $this->readCookie($name);

        if ($cookie === null) {
            return null;
        }

        $decrypted = decrypt(rawurldecode($cookie), false);

        if (! is_string($decrypted)) {
            return null;
        }

        $hasPrefix = str_starts_with($decrypted, CookieValuePrefix::create($name, Crypt::getKey()));

        return $hasPrefix ? CookieValuePrefix::remove($decrypted) : $decrypted;
    }

    /**
     * Get or set an unencrypted cookie's value.
     *
     * @param  array<string, mixed>  $options
     */
    public function plainCookie(string $name, ?string $value = null, DateTimeInterface|int|null $expiry = null, array $options = []): static|string|null
    {
        if ($value !== null) {
            return $this->addCookie($name, $value, $expiry, $options, false);
        }

        $cookie = $this->readCookie($name);

        return $cookie === null ? null : rawurldecode($cookie);
    }

    /**
     * Add the given cookie to the browser context.
     *
     * @param  array<string, mixed>  $options
     */
    public function addCookie(string $name, string $value, DateTimeInterface|int|null $expiry = null, array $options = [], bool $encrypt = true): static
    {
        if ($encrypt) {
            $prefix = CookieValuePrefix::create($name, Crypt::getKey());
            $value = encrypt($prefix.$value, false);
        }

        if ($expiry instanceof DateTimeInterface) {
            $expiry = $expiry->getTimestamp();
        }

        $domain = isset($options['domain']) && is_string($options['domain']) ? $options['domain'] : null;
        $path = isset($options['path']) && is_string($options['path']) ? $options['path'] : '/';

        if ($domain !== null) {
            $cookie = ['name' => $name, 'value' => $value, 'domain' => $domain, 'path' => $path];
        } else {
            $cookie = ['name' => $name, 'value' => $value, 'url' => $this->cookieUrl()];
        }

        if ($expiry !== null) {
            $cookie['expires'] = (int) $expiry;
        }

        $this->page->context()->addCookies([$cookie]);

        return $this;
    }

    /**
     * Delete the given cookie.
     */
    public function deleteCookie(string $name): static
    {
        $this->page->context()->deleteCookie($name);

        return $this;
    }

    /**
     * The raw (URL-encoded) value of the named cookie, or null if absent.
     */
    protected function readCookie(string $name): ?string
    {
        foreach ($this->page->context()->cookies() as $cookie) {
            if (($cookie['name'] ?? null) === $name && isset($cookie['value']) && is_string($cookie['value'])) {
                return $cookie['value'];
            }
        }

        return null;
    }

    /**
     * The URL a newly-added cookie is scoped to (the current page, falling
     * back to the configured base URL for about:blank).
     */
    protected function cookieUrl(): string
    {
        $url = $this->page->url();

        if ($url === '' || str_starts_with($url, 'about:')) {
            return (string) static::$baseUrl;
        }

        return $url;
    }
}
