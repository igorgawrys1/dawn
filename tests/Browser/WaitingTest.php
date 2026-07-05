<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;
use Dawn\Exceptions\TimeoutException;

/**
 * Proves that Dusk's waitFor* methods work through Playwright's native
 * waiting - the fixtures mutate the DOM and navigate asynchronously, and no
 * test below contains a sleep.
 */
final class WaitingTest extends BrowserTestCase
{
    public function test_wait_for_selector_that_appears_later(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html')
                ->assertMissing('.late-arrival')
                ->waitFor('.late-arrival')
                ->assertSeeIn('.late-arrival', 'Loaded via JavaScript!')
                ->waitFor('@late');
        });
    }

    public function test_wait_until_missing(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html')
                ->assertVisible('#vanishing')
                ->waitUntilMissing('#vanishing')
                ->assertMissing('#vanishing');
        });
    }

    public function test_wait_for_text_and_text_in(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html')
                ->waitForText('Loaded via JavaScript!')
                ->waitForText('LOADED VIA', ignoreCase: true, seconds: 2)
                ->waitForTextIn('@status', 'ready')
                ->waitUntilMissingText('I will disappear')
                ->assertSeeIn('@status', 'ready');
        });
    }

    public function test_wait_for_link(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html')
                ->waitForLink('Late link')
                ->clickLink('Late link')
                ->assertPathIs('/second.html');
        });
    }

    public function test_wait_for_location_across_navigation(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html')
                ->press('#go-second')
                ->waitForLocation('/second.html')
                ->assertSee('The second page');
        });
    }

    public function test_wait_for_full_url_location(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html')
                ->press('#go-second')
                ->waitForLocation(static::BASE_URL.'/second.html')
                ->assertPathIs('/second.html');
        });
    }

    public function test_wait_until_javascript_expression(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html')
                ->waitUntil('window.dawnReady === true')
                ->waitUntil('return window.dawnReady;')
                ->assertScript('window.dawnReady', true);
        });
    }

    public function test_when_available_scopes_into_the_new_element(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html')
                ->whenAvailable('.late-arrival', function (Browser $late): void {
                    $late->assertSee('Loaded via JavaScript!');
                });
        });
    }

    public function test_wait_until_enabled(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->waitUntilEnabled('#delayed-button')
                ->assertButtonEnabled('#delayed-button');
        });
    }

    public function test_wait_for_reload(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html')
                ->waitForReload(function (Browser $browser): void {
                    $browser->click('#reload-link');
                })
                ->assertPathIs('/wait.html');
        });
    }

    public function test_click_and_wait_for_reload(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html')
                ->clickAndWaitForReload('#reload-link')
                ->assertVisible('#vanishing');
        });
    }

    public function test_wait_for_times_out_with_dusk_style_message(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html');

            try {
                $browser->waitFor('.never-appears', 1);

                $this->fail('Expected a TimeoutException.');
            } catch (TimeoutException $e) {
                $this->assertSame('Waited 1 seconds for selector [.never-appears].', $e->getMessage());
            }
        });
    }

    public function test_wait_for_text_times_out_with_dusk_style_message(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/wait.html');

            try {
                $browser->waitForText('text that never shows', 1);

                $this->fail('Expected a TimeoutException.');
            } catch (TimeoutException $e) {
                $this->assertSame('Waited 1 seconds for text [text that never shows].', $e->getMessage());
            }
        });
    }

    public function test_wait_using_php_callback(): void
    {
        $this->browse(function (Browser $browser): void {
            $calls = 0;

            $browser->visit('/wait.html')
                ->waitUsing(5, 100, function () use (&$calls): bool {
                    $calls++;

                    return $calls >= 3;
                });

            $this->assertSame(3, $calls);
        });
    }

    public function test_pause_runs_in_the_browser_event_loop(): void
    {
        $this->browse(function (Browser $browser): void {
            $start = microtime(true);

            $browser->visit('/index.html')->pause(150);

            $this->assertGreaterThan(0.12, microtime(true) - $start);
        });
    }
}
