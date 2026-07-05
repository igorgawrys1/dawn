<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;

final class FrameTest extends BrowserTestCase
{
    public function test_within_frame_scopes_reads_and_actions(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/frames.html')
                ->assertSee('Outer page')
                ->withinFrame('@content-frame', function (Browser $frame): void {
                    $frame->assertSee('Inside the frame')
                        ->assertSeeIn('@inner-heading', 'Inside the frame')
                        ->assertVisible('@inner-button')
                        ->assertInputValue('inner-field', 'framed')
                        ->type('inner-field', 'typed in frame')
                        ->assertInputValue('inner-field', 'typed in frame')
                        ->click('@inner-button')
                        ->assertSeeIn('@inner-heading', 'Clicked inside');
                })
                // Back in the outer document afterwards.
                ->assertSee('Outer page');
        });
    }
}
