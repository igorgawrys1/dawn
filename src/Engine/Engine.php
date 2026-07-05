<?php

declare(strict_types=1);

namespace Dawn\Engine;

use Playwright\Browser\BrowserContextInterface;
use Playwright\Browser\BrowserInterface;
use Playwright\Browser\BrowserType;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\PlaywrightClient;
use Playwright\PlaywrightFactory;

/**
 * Owns the Playwright engine lifecycle: one Node server process and one
 * launched browser per PHP process, with a cheap fresh browser context per
 * test for full isolation (cookies, storage, cache).
 */
final class Engine
{
    private static ?PlaywrightClient $client = null;

    private static ?BrowserInterface $browser = null;

    /**
     * The transport timeout is deliberately generous: waits are delegated to
     * the browser (Playwright auto-wait or Dawn's in-page condition promises),
     * so a single JSON-RPC call may legitimately block for a long wait.
     */
    private const TRANSPORT_TIMEOUT_MS = 120_000;

    public static function browser(): BrowserInterface
    {
        if (self::$browser !== null && self::$browser->isConnected()) {
            return self::$browser;
        }

        $client = self::client();

        $builder = match (self::browserType()) {
            BrowserType::FIREFOX => $client->firefox(),
            BrowserType::WEBKIT => $client->webkit(),
            default => $client->chromium(),
        };

        return self::$browser = $builder->withHeadless(self::headless())->launch();
    }

    public static function newContext(): BrowserContextInterface
    {
        return self::browser()->newContext();
    }

    /**
     * Tear down the browser and the Node server process.
     */
    public static function quit(): void
    {
        try {
            self::$browser?->close();
        } finally {
            self::$browser = null;

            try {
                self::$client?->close();
            } finally {
                self::$client = null;
            }
        }
    }

    private static function client(): PlaywrightClient
    {
        return self::$client ??= PlaywrightFactory::create(new PlaywrightConfig(
            browser: self::browserType(),
            headless: self::headless(),
            timeoutMs: self::TRANSPORT_TIMEOUT_MS,
        ));
    }

    private static function browserType(): BrowserType
    {
        return match (strtolower((string) self::env('DAWN_BROWSER', 'chromium'))) {
            'firefox' => BrowserType::FIREFOX,
            'webkit', 'safari' => BrowserType::WEBKIT,
            default => BrowserType::CHROMIUM,
        };
    }

    private static function headless(): bool
    {
        return filter_var(self::env('DAWN_HEADLESS', 'true'), FILTER_VALIDATE_BOOL);
    }

    private static function env(string $key, string $default): string
    {
        $value = getenv($key);

        return $value === false || $value === '' ? $default : $value;
    }
}
