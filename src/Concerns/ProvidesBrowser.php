<?php

declare(strict_types=1);

namespace Dawn\Concerns;

use Closure;
use Dawn\Browser;
use Dawn\Engine\Engine;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\AfterClass;
use ReflectionFunction;
use Throwable;

trait ProvidesBrowser
{
    /**
     * All of the active browser instances. Mirrors Dusk: the primary browser
     * persists across tests within a class and is closed after the class.
     *
     * @var Collection<int, Browser>|null
     */
    protected static ?Collection $browsers = null;

    /**
     * @var list<Closure(): void>
     */
    protected static array $afterClassCallbacks = [];

    #[AfterClass]
    public static function tearDownDawnClass(): void
    {
        static::closeAll();

        foreach (static::$afterClassCallbacks as $callback) {
            $callback();
        }

        static::$afterClassCallbacks = [];
    }

    public static function afterClass(Closure $callback): void
    {
        static::$afterClassCallbacks[] = $callback;
    }

    /**
     * Create one or more browsers and execute the given callback with them.
     */
    public function browse(Closure $callback): void
    {
        $browsers = $this->createBrowsersFor($callback);

        try {
            $callback(...$browsers->all());
        } catch (Throwable $e) {
            $this->captureFailuresFor($browsers);

            throw $e;
        } finally {
            $this->storeConsoleLogsFor($browsers);

            static::$browsers = $this->closeAllButPrimary($browsers);
        }
    }

    /**
     * @return Collection<int, Browser>
     */
    protected function createBrowsersFor(Closure $callback): Collection
    {
        if (static::$browsers === null || static::$browsers->isEmpty()) {
            static::$browsers = new Collection([$this->newBrowser()]);
        }

        $additional = $this->browsersNeededFor($callback) - static::$browsers->count();

        for ($i = 0; $i < $additional; $i++) {
            static::$browsers->push($this->newBrowser());
        }

        return static::$browsers;
    }

    protected function newBrowser(): Browser
    {
        // Note: the engine's page->setDefaultTimeout() is not implemented by
        // its Node server (v1.2.0), so Dawn passes an explicit timeout with
        // every locator action instead - see Browser::actionOptions().
        return new Browser(Engine::newContext()->newPage());
    }

    protected function browsersNeededFor(Closure $callback): int
    {
        return (new ReflectionFunction($callback))->getNumberOfParameters();
    }

    /**
     * Capture a failure screenshot for each browser.
     *
     * @param  Collection<int, Browser>  $browsers
     */
    protected function captureFailuresFor(Collection $browsers): void
    {
        $browsers->each(function (Browser $browser, int $key): void {
            // A dialog left open by the failing test blocks the page, so any
            // screenshot would hang; dismiss pending dialogs first, and never
            // let a capture failure mask the test's real error.
            try {
                $browser->dismissPendingDialogs();

                $browser->screenshot('failure-'.$this->getCallerName().'-'.$key);
            } catch (Throwable) {
                // Best-effort artifact capture.
            }
        });
    }

    /**
     * Store the console output for each browser and reset the buffers.
     *
     * @param  Collection<int, Browser>  $browsers
     */
    protected function storeConsoleLogsFor(Collection $browsers): void
    {
        $browsers->each(function (Browser $browser, int $key): void {
            $browser->storeConsoleLog($this->getCallerName().'-'.$key);

            $browser->consoleMessages = [];
        });
    }

    /**
     * @param  Collection<int, Browser>  $browsers
     * @return Collection<int, Browser>
     */
    protected function closeAllButPrimary(Collection $browsers): Collection
    {
        $browsers->slice(1)->each(static fn (Browser $browser) => $browser->quit());

        return $browsers->take(1);
    }

    public static function closeAll(): void
    {
        if (static::$browsers !== null) {
            static::$browsers->each(static function (Browser $browser): void {
                try {
                    $browser->quit();
                } catch (Throwable) {
                    // The context may already be gone; closing is best-effort.
                }
            });
        }

        static::$browsers = null;
    }

    /**
     * The current test name, sanitised for use in file names.
     */
    protected function getCallerName(): string
    {
        $name = str_replace('\\', '_', get_class($this)).'_'.$this->name();

        return (string) preg_replace('/[^\w.-]/', '_', $name);
    }
}
