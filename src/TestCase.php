<?php

declare(strict_types=1);

namespace Dawn;

use Dawn\Engine\Engine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\BeforeClass;

/**
 * Drop-in replacement for Laravel\Dusk\TestCase: change your DuskTestCase to
 * extend this class and your existing test bodies run on Playwright.
 */
abstract class TestCase extends FoundationTestCase
{
    use Concerns\ProvidesBrowser;

    /**
     * Register an engine shutdown once per process.
     */
    #[BeforeClass]
    public static function prepareDawn(): void
    {
        static $registered = false;

        if (! $registered) {
            $registered = true;

            register_shutdown_function(static function (): void {
                Engine::quit();
            });
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureRefreshDatabaseIsNotUsed();

        Browser::$userResolver = fn (): \Illuminate\Contracts\Auth\Authenticatable => $this->user();

        Browser::$baseUrl = $this->baseUrl();

        Browser::$storeScreenshotsAt = base_path('tests/Browser/screenshots');

        Browser::$storeConsoleLogAt = base_path('tests/Browser/console');

        Browser::$storeSourceAt = base_path('tests/Browser/source');
    }

    /**
     * The default user for Browser::login(); override in your test case.
     */
    protected function user(): \Illuminate\Contracts\Auth\Authenticatable
    {
        throw new \LogicException('User resolver has not been set. Override the user() method on your test case.');
    }

    /**
     * The base URL for all relative browser URLs.
     */
    protected function baseUrl(): string
    {
        $url = config('app.url');

        return rtrim(is_string($url) ? $url : '', '/');
    }

    /**
     * Browser tests run against a real HTTP server in a separate process;
     * RefreshDatabase wraps this process's connection in a transaction the
     * server never sees. Fail fast with a clear message instead of letting
     * the suite fail mysteriously - use DatabaseMigrations or
     * DatabaseTruncation instead.
     */
    protected function ensureRefreshDatabaseIsNotUsed(): void
    {
        $uses = (new Collection(class_uses_recursive(static::class)))->flip();

        if ($uses->has(RefreshDatabase::class)) {
            throw new \LogicException(
                'Dawn (like Dusk) does not support the RefreshDatabase trait - the browser talks to your app '
                .'over HTTP in another process, which cannot see this process\'s database transaction. '
                .'Use DatabaseMigrations or DatabaseTruncation instead.'
            );
        }
    }
}
