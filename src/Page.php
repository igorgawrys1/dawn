<?php

declare(strict_types=1);

namespace Dawn;

/**
 * Base class for Dusk-style page objects.
 */
abstract class Page
{
    /**
     * Get the URL for the page.
     */
    abstract public function url(): string;

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        //
    }

    /**
     * The element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [];
    }

    /**
     * The element shortcuts shared by all pages of the site.
     *
     * @return array<string, string>
     */
    public static function siteElements(): array
    {
        return [];
    }
}
