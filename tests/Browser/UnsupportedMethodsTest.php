<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;
use Dawn\Exceptions\UnsupportedDuskMethod;

final class UnsupportedMethodsTest extends BrowserTestCase
{
    public function test_unimplemented_dusk_methods_throw_typed_exceptions(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html');

            foreach (['maximize', 'tinker', 'acceptDialog'] as $method) {
                try {
                    $browser->{$method}();

                    $this->fail("Expected [{$method}] to throw UnsupportedDuskMethod.");
                } catch (UnsupportedDuskMethod $e) {
                    $this->assertStringContainsString($method, $e->getMessage());
                    $this->assertStringContainsString('COMPATIBILITY.md', $e->getMessage());
                }
            }
        });
    }

    public function test_unknown_methods_throw_typed_exceptions(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->expectException(UnsupportedDuskMethod::class);

            /** @phpstan-ignore-next-line intentionally calling an undefined method */
            $browser->definitelyNotADuskMethod();
        });
    }

    public function test_macros_still_work(): void
    {
        Browser::macro('assertHeadlineSays', function (string $text) {
            /** @var Browser $this */
            return $this->assertSeeIn('@headline', $text);
        });

        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')->assertHeadlineSays('Welcome to Dawn');
        });
    }
}
