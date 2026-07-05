<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;

/**
 * Plain (unencrypted) cookie coverage. Encrypted cookies require a Laravel
 * app (encrypter / APP_KEY) and are exercised in the integration suite.
 */
final class CookieTest extends BrowserTestCase
{
    public function test_set_get_and_delete_plain_cookie(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->plainCookie('flavour', 'chocolate')
                ->assertHasPlainCookie('flavour')
                ->assertPlainCookieValue('flavour', 'chocolate');

            $this->assertSame('chocolate', $browser->plainCookie('flavour'));

            $browser->deleteCookie('flavour')
                ->assertPlainCookieMissing('flavour');

            $this->assertNull($browser->plainCookie('flavour'));
        });
    }

    public function test_missing_cookie_reads_as_null(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->assertPlainCookieMissing('never-set');

            $this->assertNull($browser->plainCookie('never-set'));
        });
    }
}
