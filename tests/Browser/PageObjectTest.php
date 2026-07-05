<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;
use Dawn\Component;
use Dawn\Page;

final class PageObjectTest extends BrowserTestCase
{
    public function test_visit_page_object_runs_its_assertions_and_elements(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit(new FixtureFormPage)
                ->assertPathIs('/form.html')
                // "@name-field" is a page element shortcut, not a dusk attribute.
                ->type('@name-field', 'Taylor')
                ->assertInputValue('name', 'Taylor');
        });
    }

    public function test_on_asserts_current_page(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->on(new FixtureFormPage)
                ->assertSee('Registration');
        });
    }

    public function test_component_scopes_and_exposes_elements(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/form.html')
                ->within(new TermsPanel, function (Browser $panel): void {
                    $panel->assertSee('The terms panel text.')
                        ->check('@terms-checkbox')
                        ->assertChecked('@terms-checkbox');
                });
        });
    }
}

final class FixtureFormPage extends Page
{
    public function url(): string
    {
        return '/form.html';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs('/form.html');
    }

    /**
     * @return array<string, string>
     */
    public function elements(): array
    {
        return ['@name-field' => '#name-field'];
    }
}

final class TermsPanel extends Component
{
    public function selector(): string
    {
        return '[dusk="terms-panel"]';
    }

    public function assert(Browser $browser): void
    {
        // The browser is already scoped to the component here, so assertions
        // target elements inside it.
        $browser->assertSee('The terms panel text.');
    }

    /**
     * @return array<string, string>
     */
    public function elements(): array
    {
        return ['@terms-checkbox' => 'input[name=terms]'];
    }
}
