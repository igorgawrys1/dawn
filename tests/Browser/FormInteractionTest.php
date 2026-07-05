<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;

final class FormInteractionTest extends BrowserTestCase
{
    public function test_typing_appending_and_clearing(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->type('name', 'Taylor')
                ->assertInputValue('name', 'Taylor')
                ->type('name', 'Igor')
                ->assertInputValue('name', 'Igor')
                ->append('name', ' Gawryś')
                ->assertInputValue('name', 'Igor Gawryś')
                ->clear('name')
                ->assertInputValue('name', '')
                ->type('#name-field', 'By id')
                ->assertInputValue('name', 'By id')
                ->type('bio', 'A textarea value')
                ->assertInputValue('bio', 'A textarea value');
        });
    }

    public function test_prefilled_values_and_input_assertions(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->assertInputValue('email', 'prefilled@example.com')
                ->assertInputValueIsNot('email', 'other@example.com')
                ->assertInputPresent('email')
                ->assertInputMissing('nonexistent')
                ->assertValue('input[name=email]', 'prefilled@example.com')
                ->assertValueIsNot('input[name=email]', 'nope')
                ->assertAttribute('input[name=email]', 'type', 'email')
                ->assertAttributeContains('input[name=email]', 'value', 'prefilled')
                ->assertAttributeMissing('input[name=email]', 'data-missing');
        });
    }

    public function test_selects(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->select('role', 'editor')
                ->assertSelected('role', 'editor')
                ->assertNotSelected('role', 'admin')
                ->assertSelectHasOption('role', 'admin')
                ->assertSelectHasOptions('role', ['admin', 'editor'])
                ->assertSelectMissingOption('role', 'superadmin')
                ->assertSelectMissingOptions('role', ['superadmin', 'guest'])
                ->select('tags[]', ['php', 'go'])
                ->assertSelected('tags[]', 'php')
                ->assertSelected('tags[]', 'go')
                ->assertNotSelected('tags[]', 'js')
                ->select('role');

            $this->assertContains($browser->value('select[name=role]'), ['admin', 'editor']);
        });
    }

    public function test_radios_and_checkboxes(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->radio('plan', 'pro')
                ->assertRadioSelected('plan', 'pro')
                ->assertRadioNotSelected('plan', 'free')
                ->check('terms')
                ->assertChecked('terms')
                ->uncheck('terms')
                ->assertNotChecked('terms')
                ->assertChecked('newsletter')
                ->uncheck('newsletter')
                ->assertNotChecked('newsletter');
        });
    }

    public function test_file_attachment(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dawn');
        file_put_contents((string) $path, 'avatar-bytes');

        try {
            $this->browse(function (Browser $browser) use ($path): void {
                $browser->visit('/form.html')
                    ->attach('avatar', (string) $path)
                    ->assertScript("document.querySelector('input[name=avatar]').files.length === 1");
            });
        } finally {
            @unlink((string) $path);
        }
    }

    public function test_buttons_resolved_by_selector_name_value_and_text(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->press('Register Now')
                ->assertPathIs('/second.html')
                ->visit('/form.html')
                ->press('Create Account')
                ->assertPathIs('/second.html')
                ->visit('/form.html')
                ->press('@submit-button')
                ->assertPathIs('/second.html');
        });
    }

    public function test_press_and_wait_for_reenabled_button(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->assertButtonDisabled('#delayed-button')
                ->pressAndWaitFor('#delayed-button', 3)
                ->assertButtonEnabled('#delayed-button');
        });
    }

    public function test_keyboard_input(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->keys('input[name=name]', 'dawn', '{home}', 'the ')
                ->assertInputValue('name', 'the dawn')
                ->keys('input[name=name]', '{end}', ['{shift}', 'x'])
                ->assertInputValue('name', 'the dawnX');
        });
    }

    public function test_focus_and_enabled_assertions(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->click('input[name=name]')
                ->assertFocused('name')
                ->assertNotFocused('email')
                ->assertEnabled('email')
                ->assertDisabled('#delayed-button');
        });
    }

    public function test_typing_slowly_delegates_delay_to_the_browser(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->typeSlowly('name', 'abc', 10)
                ->assertInputValue('name', 'abc')
                ->appendSlowly('name', 'def', 10)
                ->assertInputValue('name', 'abcdef');
        });
    }
}
