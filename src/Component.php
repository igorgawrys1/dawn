<?php

declare(strict_types=1);

namespace Dawn;

use Stringable;

/**
 * Base class for Dusk-style component objects.
 */
abstract class Component implements Stringable
{
    /**
     * Get the root selector for the component.
     */
    abstract public function selector(): string;

    /**
     * Assert that the browser page contains the component.
     */
    public function assert(Browser $browser): void
    {
        //
    }

    /**
     * The element shortcuts for the component.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [];
    }

    public function __toString(): string
    {
        return $this->selector();
    }
}
