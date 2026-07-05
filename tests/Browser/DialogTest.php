<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;
use Dawn\Exceptions\UnsupportedDuskMethod;

final class DialogTest extends BrowserTestCase
{
    /**
     * Driving blocking JS dialogs is not supported on the playwright-php
     * engine (synchronous transport deadlock). Each dialog method must fail
     * loudly and clearly rather than hang.
     *
     * @return array<string, array{0: string, 1: array<int, mixed>}>
     */
    public static function dialogMethods(): array
    {
        return [
            'acceptDialog' => ['acceptDialog', []],
            'dismissDialog' => ['dismissDialog', []],
            'typeInDialog' => ['typeInDialog', ['value']],
            'waitForDialog' => ['waitForDialog', []],
            'assertDialogOpened' => ['assertDialogOpened', ['message']],
        ];
    }

    /**
     * @param  array<int, mixed>  $args
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dialogMethods')]
    public function test_dialog_methods_throw_a_clear_exception(string $method, array $args): void
    {
        $this->browse(function (Browser $browser) use ($method, $args): void {
            $browser->visit('/dialogs.html');

            try {
                $browser->{$method}(...$args);

                $this->fail("Expected [{$method}] to throw UnsupportedDuskMethod.");
            } catch (UnsupportedDuskMethod $e) {
                $this->assertStringContainsString($method, $e->getMessage());
            }
        });
    }

    public function test_wait_for_event(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/dialogs.html')
                ->click('#defer-event')
                ->waitForEvent('dawn:ready', '@event-target')
                ->assertPresent('@event-target');
        });
    }
}
