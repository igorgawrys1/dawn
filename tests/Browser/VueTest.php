<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;

final class VueTest extends BrowserTestCase
{
    public function test_reads_vue3_setup_state(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/vue.html')
                ->assertVue('count', 1, '@vue3')
                ->assertVue('name', 'Taylor', '@vue3')
                ->assertVueIsNot('count', 2, '@vue3')
                ->assertVueContains('tags', 'php', '@vue3')
                ->assertVueDoesNotContain('tags', 'python', '@vue3');

            $this->assertSame('Taylor', $browser->vueAttribute('@vue3', 'name'));
        });
    }

    public function test_reads_vue2_instance(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/vue.html')
                ->assertVue('greeting', 'hello', '@vue2')
                ->assertVueContains('items', 'a', '@vue2');
        });
    }

    public function test_wait_until_vue(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/vue.html')
                ->assertVue('count', 1, '@vue3')
                ->click('#bump')
                ->waitUntilVue('count', 5, '@vue3')
                ->assertVue('count', 5, '@vue3');
        });
    }
}
