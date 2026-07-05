<?php

declare(strict_types=1);

namespace Dawn;

/**
 * Global Dawn configuration, mirroring the Laravel\Dusk\Dusk static surface.
 */
final class Dawn
{
    /**
     * The HTML attribute targeted by "@" selectors (e.g. "@login-button"
     * becomes "[dusk=login-button]"). Kept as "dusk" by default so existing
     * Dusk suites and blade templates work unchanged.
     */
    public static string $selectorHtmlAttribute = 'dusk';

    public static function selectorHtmlAttribute(string $attribute): void
    {
        self::$selectorHtmlAttribute = $attribute;
    }
}
