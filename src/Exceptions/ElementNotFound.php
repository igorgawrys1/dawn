<?php

declare(strict_types=1);

namespace Dawn\Exceptions;

use RuntimeException;

/**
 * Thrown when a selector matches no element at the moment an immediate
 * (non-waiting) read runs - the counterpart of Dusk's NoSuchElementException,
 * without depending on any WebDriver types.
 */
final class ElementNotFound extends RuntimeException
{
    public static function forSelector(string $selector): self
    {
        return new self("Unable to locate element with selector [{$selector}].");
    }
}
