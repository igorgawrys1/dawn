<?php

declare(strict_types=1);

namespace Dawn\Exceptions;

use BadMethodCallException;

/**
 * Thrown when a Dusk Browser method is not (yet) implemented by Dawn.
 *
 * Dawn never approximates or silently ignores unsupported Dusk calls; the
 * compatibility table documents the status and roadmap of every method.
 */
final class UnsupportedDuskMethod extends BadMethodCallException
{
    public const COMPATIBILITY_TABLE = 'https://github.com/igorgawrys1/dawn/blob/main/COMPATIBILITY.md';

    public static function make(string $method, ?string $reason = null): self
    {
        $message = sprintf(
            'The Dusk method [%s] is not supported by Dawn%s. See the compatibility table for status and alternatives: %s',
            $method,
            $reason !== null ? ' ('.$reason.')' : ' yet',
            self::COMPATIBILITY_TABLE.'#'.strtolower($method),
        );

        return new self($message);
    }
}
