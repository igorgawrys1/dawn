<?php

declare(strict_types=1);

namespace Dawn\Tests\Browser;

use Dawn\Browser;

final class NavigationTest extends BrowserTestCase
{
    public function test_visit_and_title_assertions(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->assertTitle('Dawn Fixture')
                ->assertTitleContains('Dawn')
                ->assertSee('Welcome to Dawn')
                ->assertDontSee('You cannot see me');
        });
    }

    public function test_click_link_and_history_navigation(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->clickLink('Second page')
                ->assertPathIs('/second.html')
                ->assertSee('The second page')
                ->back()
                ->assertPathIs('/index.html')
                ->forward()
                ->assertPathIs('/second.html')
                ->refresh()
                ->assertTitle('Second Page');
        });
    }

    public function test_url_query_and_fragment_assertions(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->clickLink('Filtered results')
                ->assertPathIs('/index.html')
                ->assertQueryStringHas('page', '2')
                ->assertQueryStringHas('sort', 'name')
                ->assertQueryStringMissing('filter')
                ->assertFragmentIs('results')
                ->assertSchemeIs('http')
                ->assertHostIs('127.0.0.1')
                ->assertPortIs(8899);
        });
    }

    public function test_path_wildcards_and_url_assertions(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/second.html')
                ->assertPathIs('/*.html')
                ->assertPathBeginsWith('/second')
                ->assertPathEndsWith('.html')
                ->assertPathContains('econd')
                ->assertPathIsNot('/index.html')
                ->assertUrlIs(static::BASE_URL.'/second.html');
        });
    }

    public function test_script_execution_and_assertions(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->assertScript('document.title === "Dawn Fixture"')
                ->assertScript('return 2 + 2', 4);

            $results = $browser->script([
                'return document.querySelectorAll("a").length;',
                'document.title = "Changed"; return document.title;',
            ]);

            $this->assertSame(3, is_numeric($results[0]) ? (int) $results[0] : $results[0]);
            $this->assertSame('Changed', $results[1]);
        });
    }

    public function test_source_assertions_and_resize(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/index.html')
                ->assertSourceHas('<h1 dusk="headline">')
                ->assertSourceMissing('<h2>')
                ->resize(1024, 768)
                ->assertSee('Welcome to Dawn');
        });
    }
}
