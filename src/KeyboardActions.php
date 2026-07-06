<?php

declare(strict_types=1);

namespace Dawn;

use Playwright\Page\PageInterface;

/**
 * Fluent keyboard wrapper, the Dawn equivalent of Laravel\Dusk\Keyboard.
 *
 * Normally received via the Browser::withKeyboard() callback, but - like Dusk's
 * Keyboard, which is a plain public class - it can also be constructed directly.
 * To stay compatible with both Dusk's `new Keyboard($browser)` and Dawn's own
 * construction, the constructor accepts either a Dawn Browser or the underlying
 * Playwright page (`Playwright\Page\PageInterface`).
 */
final class KeyboardActions
{
    private readonly PageInterface $page;

    public function __construct(Browser|PageInterface $browserOrPage)
    {
        $this->page = $browserOrPage instanceof Browser ? $browserOrPage->page : $browserOrPage;
    }

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
