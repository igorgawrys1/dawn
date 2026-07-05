<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;

final class ScopingTest extends BrowserTestCase
{
    public function test_dusk_attribute_selectors(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->assertVisible('@headline')
                ->assertSeeIn('@headline', 'Welcome to Dawn')
                ->assertAttribute('@headline', 'dusk', 'headline')
                ->assertPresent('@panel')
                ->assertMissing('@nonexistent');
        });
    }

    public function test_within_scopes_all_operations(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->within('@panel', function (Browser $panel): void {
                    $panel->assertSee('Panel Title')
                        ->assertDontSee('Welcome to Dawn')
                        ->assertSeeLink('Panel link')
                        ->assertDontSeeLink('Second page');
                });
        });
    }

    public function test_nested_within_and_elsewhere(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->within('@terms-panel', function (Browser $panel): void {
                    $panel->assertSee('The terms panel text.')
                        ->check('terms')
                        ->assertChecked('terms')
                        ->elsewhere('form', function (Browser $form): void {
                            $form->assertNotChecked('terms');
                        });
                });
        });
    }

    public function test_scoped_checkbox_resolution_targets_scope_not_document(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->within('@registration-form', function (Browser $form): void {
                    $form->check('terms')->assertChecked('terms');
                })
                ->within('@terms-panel', function (Browser $panel): void {
                    $panel->assertNotChecked('terms');
                });
        });
    }

    public function test_element_helpers(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html');

            $this->assertSame('Welcome to Dawn', $browser->text('@headline'));
            $this->assertSame('headline', $browser->attribute('@headline', 'dusk'));
            $this->assertNotNull($browser->element('@headline'));
            $this->assertNull($browser->element('.does-not-exist'));
            $this->assertCount(3, $browser->elements('a'));

            $browser->assertCount('a', 3);
        });
    }

    public function test_screenshots_and_console_log_capture(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->screenshot('scoping-test')
                ->screenshotElement('@panel', 'scoping-panel')
                ->storeConsoleLog('scoping-console');

            $this->assertFileExists(__DIR__.'/screenshots/scoping-test.png');
            $this->assertFileExists(__DIR__.'/screenshots/scoping-panel.png');
        });
    }

    public function test_scroll_into_view(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->scrollIntoView('@panel')
                ->assertVisible('@panel');
        });
    }
}
