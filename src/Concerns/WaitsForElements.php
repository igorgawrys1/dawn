<?php

declare(strict_types=1);

namespace Dawn\Concerns;

use Closure;
use Dawn\Exceptions\TimeoutException;
use Dawn\Exceptions\UnsupportedDuskMethod;
use Dawn\Support\Waiter;
use Illuminate\Support\Str;
use Playwright\Exception\PlaywrightExceptionInterface;

trait WaitsForElements
{
    /**
     * Execute the given callback within a scoped browser once the selector is available.
     */
    public function whenAvailable(string $selector, Closure $callback, ?int $seconds = null): static
    {
        return $this->waitFor($selector, $seconds)->with($selector, $callback);
    }

    /**
     * Wait for the element matching the given selector to be visible.
     */
    public function waitFor(string $selector, ?int $seconds = null): static
    {
        return $this->waitForSelectorState(
            $selector,
            'visible',
            $seconds,
            $this->formatTimeOutMessage('Waited %s seconds for selector', $selector),
        );
    }

    /**
     * Wait for the element matching the given selector to be removed or hidden.
     */
    public function waitUntilMissing(string $selector, ?int $seconds = null): static
    {
        return $this->waitForSelectorState(
            $selector,
            'hidden',
            $seconds,
            $this->formatTimeOutMessage('Waited %s seconds for removal of selector', $selector),
        );
    }

    /**
     * Wait for the given text to be removed from the current scope.
     *
     * @param  string|list<string>  $text
     */
    public function waitUntilMissingText(string|array $text, ?int $seconds = null): static
    {
        $text = is_array($text) ? array_values($text) : [$text];

        $message = $this->formatTimeOutMessage('Waited %s seconds for removal of text', implode("', '", $text));

        return $this->waitForJsCondition(
            $this->textPresenceExpression($this->resolver->format(''), $text, false, negate: true),
            $seconds,
            $message,
        );
    }

    /**
     * Wait for the given text to appear within the current scope.
     *
     * @param  string|list<string>  $text
     */
    public function waitForText(string|array $text, ?int $seconds = null, bool $ignoreCase = false): static
    {
        $text = is_array($text) ? array_values($text) : [$text];

        $message = $this->formatTimeOutMessage('Waited %s seconds for text', implode("', '", $text));

        return $this->waitForJsCondition(
            $this->textPresenceExpression($this->resolver->format(''), $text, $ignoreCase),
            $seconds,
            $message,
        );
    }

    /**
     * Wait for the given text to appear within the given selector.
     *
     * @param  string|list<string>  $text
     */
    public function waitForTextIn(string $selector, string|array $text, ?int $seconds = null, bool $ignoreCase = false): static
    {
        $text = is_array($text) ? array_values($text) : [$text];

        $message = 'Waited %s seconds for text "'
            .$this->escapePercentCharacters(implode("', '", $text))
            .'" in selector '.$this->escapePercentCharacters($selector);

        return $this->waitForJsCondition(
            $this->textPresenceExpression($this->resolver->format($selector), $text, $ignoreCase),
            $seconds,
            $message,
        );
    }

    /**
     * Wait for the link with the given text to be visible.
     */
    public function waitForLink(string $link, ?int $seconds = null): static
    {
        $message = $this->formatTimeOutMessage('Waited %s seconds for link', $link);

        try {
            $this->linkLocator($link)->first()->waitFor([
                'state' => 'attached',
                'timeout' => $this->waitTimeoutInMilliseconds($seconds),
            ]);
        } catch (PlaywrightExceptionInterface $e) {
            throw $this->convertTimeout($e, $message, $seconds);
        }

        return $this;
    }

    /**
     * Wait for an input field with the given name to be present.
     */
    public function waitForInput(string $field, ?int $seconds = null): static
    {
        $message = $this->formatTimeOutMessage('Waited %s seconds for input', $field);

        $prefix = $this->resolver->prefix;

        $selector = "{$prefix} input[name='{$field}'], {$prefix} textarea[name='{$field}'], {$prefix} select[name='{$field}']";

        try {
            $this->page->locator($selector)->first()->waitFor([
                'state' => 'attached',
                'timeout' => $this->waitTimeoutInMilliseconds($seconds),
            ]);
        } catch (PlaywrightExceptionInterface $e) {
            throw $this->convertTimeout($e, $message, $seconds);
        }

        return $this;
    }

    /**
     * Wait for the current location to match the given path or URL.
     * Semantics match Dusk exactly: pathname comparison for paths, and
     * protocol + host + pathname comparison for full URLs (query ignored).
     */
    public function waitForLocation(string $path, ?int $seconds = null): static
    {
        $message = $this->formatTimeOutMessage('Waited %s seconds for location', $path);

        $expression = Str::startsWith($path, ['http://', 'https://'])
            ? '`${location.protocol}//${location.host}${location.pathname}` == '.json_encode($path)
            : 'window.location.pathname == '.json_encode($path);

        return $this->waitForJsCondition($expression, $seconds, $message);
    }

    /**
     * Wait for the current location to match the given named route.
     *
     * @param  array<array-key, mixed>  $parameters
     */
    public function waitForRoute(string $route, array $parameters = [], ?int $seconds = null): static
    {
        return $this->waitForLocation(route($route, $parameters, false), $seconds);
    }

    /**
     * Wait for the element matching the given selector to be enabled.
     */
    public function waitUntilEnabled(string $selector, ?int $seconds = null): static
    {
        $message = $this->formatTimeOutMessage('Waited %s seconds for element to be enabled', $selector);

        $target = json_encode($this->resolver->format($selector));

        return $this->waitForJsCondition(
            "(() => { const el = document.querySelector({$target}); return !!el && !el.disabled; })()",
            $seconds,
            $message,
        );
    }

    /**
     * Wait for the element matching the given selector to be disabled.
     */
    public function waitUntilDisabled(string $selector, ?int $seconds = null): static
    {
        $message = $this->formatTimeOutMessage('Waited %s seconds for element to be disabled', $selector);

        $target = json_encode($this->resolver->format($selector));

        return $this->waitForJsCondition(
            "(() => { const el = document.querySelector({$target}); return !!el && !!el.disabled; })()",
            $seconds,
            $message,
        );
    }

    /**
     * Wait until the given JavaScript expression evaluates to true.
     */
    public function waitUntil(string $script, ?int $seconds = null, ?string $message = null): static
    {
        $expression = rtrim(trim($script), ';');

        if (str_starts_with($expression, 'return ')) {
            $expression = substr($expression, strlen('return '));
        }

        $message ??= $this->formatTimeOutMessage('Waited %s seconds for script', $script);

        return $this->waitForJsCondition($expression, $seconds, $message);
    }

    /**
     * Wait for the page to reload after executing the given callback.
     *
     * @param  (Closure(static): void)|null  $callback
     */
    public function waitForReload(?Closure $callback = null, ?int $seconds = null): static
    {
        $token = Str::random();

        $this->page->evaluate("() => { window['{$token}'] = {}; }");

        if ($callback !== null) {
            $callback($this);
        }

        return $this->waitForJsCondition(
            "typeof window['{$token}'] === 'undefined'",
            $seconds,
            'Waited %s seconds for page reload.',
        );
    }

    /**
     * Click an element and wait for the page to reload.
     */
    public function clickAndWaitForReload(?string $selector = null, ?int $seconds = null): static
    {
        return $this->waitForReload(function (self $browser) use ($selector): void {
            $browser->click($selector);
        }, $seconds);
    }

    /**
     * Wait until the given PHP callback returns true.
     *
     * This is the ONE Dusk wait that cannot be delegated to Playwright - the
     * condition is an arbitrary PHP closure - and is served by Dawn's single,
     * documented PHP-side retry helper. Prefer waitFor* / waitUntil (JS) waits.
     */
    public function waitUsing(int|float|null $seconds, int $interval, Closure $callback, ?string $message = null): static
    {
        (new Waiter)->waitUsing($seconds ?? static::$waitSeconds, $interval, $callback, $message);

        return $this;
    }

    public function waitUntilVue(string $key, mixed $value, ?string $componentSelector = null, ?int $seconds = null): static
    {
        throw UnsupportedDuskMethod::make('waitUntilVue');
    }

    public function waitUntilVueIsNot(string $key, mixed $value, ?string $componentSelector = null, ?int $seconds = null): static
    {
        throw UnsupportedDuskMethod::make('waitUntilVueIsNot');
    }

    public function waitForDialog(?int $seconds = null): static
    {
        throw UnsupportedDuskMethod::make('waitForDialog');
    }

    public function waitForEvent(string $type, ?string $target = null, ?int $seconds = null): static
    {
        throw UnsupportedDuskMethod::make('waitForEvent');
    }

    /**
     * Wait natively (in Playwright) for a locator state on a Dusk selector.
     */
    protected function waitForSelectorState(string $selector, string $state, ?int $seconds, string $message): static
    {
        try {
            $this->resolver->resolve($selector)->waitFor([
                'state' => $state,
                'timeout' => $this->waitTimeoutInMilliseconds($seconds),
            ]);
        } catch (PlaywrightExceptionInterface $e) {
            throw $this->convertTimeout($e, $message, $seconds);
        }

        return $this;
    }

    /**
     * Wait for a JavaScript expression to become truthy - inside the browser.
     *
     * One evaluate() call installs a Promise that re-checks the expression on
     * each animation frame and self-resolves false at the deadline; this is
     * the same mechanism Playwright's own waitForFunction uses. If a
     * navigation destroys the execution context mid-wait (expected for
     * location waits), the promise is re-armed on the new document. The loop
     * below therefore iterates only in reaction to navigations - it is not a
     * timed poll, and no PHP-side sleeping is involved.
     */
    protected function waitForJsCondition(string $expression, int|float|null $seconds, string $message): static
    {
        $seconds ??= static::$waitSeconds;

        $deadline = microtime(true) + $seconds;

        while (true) {
            $remaining = (int) round(($deadline - microtime(true)) * 1000);

            if ($remaining <= 0) {
                throw new TimeoutException(sprintf($message, $seconds));
            }

            $script = str_replace(
                ['__DAWN_TIMEOUT__', '__DAWN_CONDITION__'],
                [(string) $remaining, $expression],
                <<<'JS'
                () => new Promise(resolve => {
                    const deadline = Date.now() + __DAWN_TIMEOUT__;
                    const check = () => {
                        let result = false;
                        try { result = !!(__DAWN_CONDITION__); } catch (e) { result = false; }
                        if (result) return resolve(true);
                        if (Date.now() > deadline) return resolve(false);
                        requestAnimationFrame(check);
                    };
                    check();
                })
                JS,
            );

            try {
                $result = $this->page->evaluate($script);
            } catch (PlaywrightExceptionInterface $e) {
                if ($this->causedByNavigation($e)) {
                    continue;
                }

                throw $e;
            }

            if ($result === true) {
                return $this;
            }

            throw new TimeoutException(sprintf($message, $seconds));
        }
    }

    /**
     * A JS expression checking for the presence (or absence) of any of the
     * given texts within the first element matching the given CSS selector.
     * Mirrors Dusk: the scope element must exist either way, and matching is
     * done against the rendered (inner) text.
     *
     * @param  list<string>  $texts
     */
    protected function textPresenceExpression(string $scopeSelector, array $texts, bool $ignoreCase, bool $negate = false): string
    {
        $scope = json_encode($scopeSelector);
        $needles = json_encode(array_values($texts));
        $lower = $ignoreCase ? 'true' : 'false';
        $check = $negate ? '!contains' : 'contains';

        return <<<JS
        (() => {
            const scope = document.querySelector({$scope});
            if (!scope) return false;
            const lower = {$lower};
            const haystack = lower ? scope.innerText.toLowerCase() : scope.innerText;
            const contains = {$needles}.some(t => haystack.includes(lower ? t.toLowerCase() : t));
            return {$check};
        })()
        JS;
    }

    /**
     * Whether the given engine exception was caused by a navigation
     * destroying the page's JavaScript execution context - the expected
     * signal, mid-wait, that the page moved on and the in-browser condition
     * promise must be re-armed on the new document.
     */
    protected function causedByNavigation(PlaywrightExceptionInterface $e): bool
    {
        return Str::contains($e->getMessage(), [
            'Execution context was destroyed',
            'Cannot find context with specified id',
            'because of a navigation',
        ], ignoreCase: true);
    }

    /**
     * Convert an engine timeout into Dawn's TimeoutException with a
     * Dusk-style message; rethrow anything that is not a timeout.
     */
    protected function convertTimeout(PlaywrightExceptionInterface $e, string $message, ?int $seconds): \Throwable
    {
        if ($e instanceof \Playwright\Exception\TimeoutException || stripos($e->getMessage(), 'timeout') !== false) {
            return new TimeoutException(sprintf($message, $seconds ?? static::$waitSeconds), 0, $e);
        }

        return $e;
    }

    protected function waitTimeoutInMilliseconds(?int $seconds): int
    {
        return ($seconds ?? static::$waitSeconds) * 1000;
    }

    protected function formatTimeOutMessage(string $message, string $expected): string
    {
        return $message.' ['.$this->escapePercentCharacters($expected).'].';
    }

    protected function escapePercentCharacters(string $expected): string
    {
        return str_replace('%', '%%', $expected);
    }
}
