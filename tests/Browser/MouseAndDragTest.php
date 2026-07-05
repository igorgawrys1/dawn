<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;

final class MouseAndDragTest extends BrowserTestCase
{
    public function test_click_variants_on_a_selector(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/interactions.html')
                ->click('#target')
                ->assertSeeIn('@log', 'click')
                ->doubleClick('#target')
                ->assertSeeIn('@log', 'dblclick')
                ->rightClick('#target')
                ->assertSeeIn('@log', 'contextmenu');
        });
    }

    public function test_click_and_hold_then_release(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/interactions.html')
                ->clickAndHold('#target')
                ->assertSeeIn('@log', 'down')
                ->releaseMouse()
                ->assertSeeIn('@log', 'up');
        });
    }

    public function test_mouseover_reveals_element(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/interactions.html')
                ->assertMissing('@reveal')
                ->mouseover('#hover-target')
                ->assertVisible('@reveal');
        });
    }

    public function test_move_mouse_and_click_at_cursor(): void
    {
        $this->browse(function (Browser $browser): void {
            // Move the pointer over the target via its center, then click with
            // no selector - the click lands at the current cursor position.
            $browser->visit('/interactions.html')
                ->mouseover('#target')
                ->click()
                ->assertSeeIn('@log', 'click');
        });
    }

    public function test_drag_offset_moves_the_element(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/interactions.html')
                ->dragRight('@box', 100)
                ->waitForText('110,10')
                ->assertSeeIn('@box-pos', '110,10');
        });
    }
}
