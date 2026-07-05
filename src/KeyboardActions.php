<?php

declare(strict_types=1);

namespace Dawn;

use Playwright\Page\PageInterface;

/**
 * Fluent keyboard wrapper handed to the callback of Browser::withKeyboard(),
 * mirroring Laravel\Dusk\Keyboard on top of Playwright's keyboard.
 */
final class KeyboardActions
{
    public function __construct(private readonly PageInterface $page) {}

    /**
     * Press (and hold) the given key(s). Dusk key tokens are translated.
     *
     * @param  string|list<string>  $key
     */
    public function press(string|array $key): self
    {
        foreach ((array) $key as $single) {
            $this->page->keyboard()->down($this->translate($single));
        }

        return $this;
    }

    /**
     * Release the given key(s).
     *
     * @param  string|list<string>  $key
     */
    public function release(string|array $key): self
    {
        foreach ((array) $key as $single) {
            $this->page->keyboard()->up($this->translate($single));
        }

        return $this;
    }

    /**
     * Type the given text or sequence of keys.
     *
     * @param  string|list<string>  $keys
     */
    public function type(string|array $keys): self
    {
        Keyboard::send($this->page, array_values((array) $keys));

        return $this;
    }

    /**
     * Pause for the given number of milliseconds, inside the browser event loop.
     */
    public function pause(int $milliseconds): self
    {
        $this->page->evaluate('ms => new Promise(resolve => setTimeout(resolve, ms))', $milliseconds);

        return $this;
    }

    private function translate(string $key): string
    {
        return str_starts_with($key, '{') && str_ends_with($key, '}')
            ? Keyboard::translate($key)
            : $key;
    }
}
