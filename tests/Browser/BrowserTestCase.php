<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;
use Dawn\Concerns\ProvidesBrowser;
use Dawn\Engine\Engine;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Base class for Dawn's own browser tests: serves the static HTML fixtures
 * with PHP's built-in web server and provides browse() without requiring a
 * Laravel application.
 */
abstract class BrowserTestCase extends TestCase
{
    use ProvidesBrowser;

    public const BASE_URL = 'http://127.0.0.1:8899';

    private static ?Process $server = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::startFixtureServer();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Browser::$baseUrl = static::BASE_URL;
        Browser::$storeScreenshotsAt = __DIR__.'/screenshots';
        Browser::$storeConsoleLogAt = __DIR__.'/console';
        Browser::$storeSourceAt = __DIR__.'/source';
    }

    protected static function startFixtureServer(): void
    {
        if (self::$server !== null && self::$server->isRunning()) {
            return;
        }

        $process = new Process([
            PHP_BINARY, '-S', '127.0.0.1:8899', '-t', dirname(__DIR__).'/fixtures',
        ]);

        $process->start();

        self::$server = $process;

        register_shutdown_function(static function (): void {
            self::$server?->stop();
            Engine::quit();
        });

        foreach (range(1, 100) as $attempt) {
            $socket = @stream_socket_client('tcp://127.0.0.1:8899', $errorCode, $errorMessage, 0.1);

            if (is_resource($socket)) {
                fclose($socket);

                return;
            }

            usleep(50_000);
        }

        throw new RuntimeException('The fixture web server did not start: '.$process->getErrorOutput());
    }
}
