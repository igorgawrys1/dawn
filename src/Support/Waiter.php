<?php

declare(strict_types=1);

namespace Dawn\Support;

use Dawn\Exceptions\TimeoutException;
use Exception;

/**
 * THE SINGLE, DELIBERATE EXCEPTION to Dawn's "all waiting is delegated to
 * Playwright" rule.
 *
 * Dusk's waitUsing() contract is "wait until this arbitrary PHP closure
 * returns truthy". Playwright cannot evaluate PHP, so the only faithful
 * implementation is a PHP-side retry loop. It lives here - and only here - so
 * that the rest of src/ stays free of sleeps and polling. Every DOM-, text-,
 * URL- or JS-condition wait in Dawn uses Playwright's native waiting instead.
 *
 * Kept behaviour-compatible with Dusk: an initial pause of one interval, the
 * closure re-tried until the deadline, thrown exceptions treated as falsy.
 */
final class Waiter
{
    public function __construct(
        private readonly Sleeper $sleeper = new NativeSleeper,
    ) {}

    /**
     * @param  callable(): mixed  $callback
     *
     * @throws TimeoutException
     */
    public function waitUsing(int|float $seconds, int $intervalMilliseconds, callable $callback, ?string $message = null): mixed
    {
        $deadline = microtime(true) + $seconds;

        $this->sleeper->sleep($intervalMilliseconds);

        while (true) {
            try {
                $result = $callback();

                if ((bool) $result) {
                    return $result;
                }
            } catch (Exception) {
                // Dusk treats exceptions thrown by the callback as "not yet".
            }

            if (microtime(true) >= $deadline) {
                throw new TimeoutException(
                    $message !== null ? sprintf($message, $seconds) : "Waited {$seconds} seconds for callback.",
                );
            }

            $this->sleeper->sleep($intervalMilliseconds);
        }
    }
}
