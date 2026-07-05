<?php

declare(strict_types=1);

namespace Dawn\Tests\Unit;

use Dawn\Browser;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Playwright\Page\PageInterface;

/**
 * URL assertion cases ported from laravel/dusk's own test suite
 * (tests/Unit/MakesUrlAssertionsTest.php, 8.x): same inputs, same
 * assertions, same expected failure messages. Dusk mocks
 * $driver->getCurrentURL(); Dawn stubs PageInterface::url().
 */
final class DuskUrlAssertionCasesTest extends TestCase
{
    public function test_assert_url_is(): void
    {
        $browser = $this->browserAt('http://www.google.com?foo=bar');

        $browser->assertUrlIs('http://www.google.com');
        $browser->assertUrlIs('*google*');

        $browser = $this->browserAt('http://www.google.com:80/test?foo=bar');

        $browser->assertUrlIs('http://www.google.com:80/test');

        try {
            $browser->assertUrlIs('http://www.google.com');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Actual URL [http://www.google.com:80/test?foo=bar] does not equal expected URL [http://www.google.com].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_scheme_is(): void
    {
        $this->browserAt('http://www.google.com?foo=bar')->assertSchemeIs('http');
        $this->browserAt('https://www.google.com:80/test?foo=bar')->assertSchemeIs('https');
        $this->browserAt('ftp://www.google.com')->assertSchemeIs('ftp');
        $this->browserAt('http://www.google.com')->assertSchemeIs('*tp*');

        try {
            $this->browserAt('http://www.google.com')->assertSchemeIs('https');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Actual scheme [http] does not equal expected scheme [https].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_scheme_is_not(): void
    {
        $this->browserAt('http://www.google.com/test')->assertSchemeIsNot('https');
        $this->browserAt('https://www.google.com/test')->assertSchemeIsNot('http');

        try {
            $this->browserAt('https://www.google.com/test')->assertSchemeIsNot('https');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Scheme [https] should not equal the actual value.',
                $e->getMessage()
            );
        }
    }

    public function test_assert_host_is(): void
    {
        $this->browserAt('http://www.google.com?foo=bar')->assertHostIs('www.google.com');
        $this->browserAt('http://google.com?foo=bar')->assertHostIs('google.com');
        $this->browserAt('https://www.laravel.com:80/test?foo=bar')->assertHostIs('www.laravel.com');

        try {
            $this->browserAt('https://www.laravel.com')->assertHostIs('testing.com');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Actual host [www.laravel.com] does not equal expected host [testing\.com].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_host_is_not(): void
    {
        $this->browserAt('http://www.google.com/test')->assertHostIsNot('laravel.com');
        $this->browserAt('https://www.laravel.com/test')->assertHostIsNot('laravel.com');
        $this->browserAt('https://laravel.com/test')->assertHostIsNot('www.laravel.com');

        try {
            $this->browserAt('https://laravel.com/test')->assertHostIsNot('laravel.com');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Host [laravel.com] should not equal the actual value.',
                $e->getMessage()
            );
        }
    }

    public function test_assert_port_is(): void
    {
        $this->browserAt('http://www.laravel.com:80/test?foo=bar')->assertPortIs('80');
        $this->browserAt('https://www.laravel.com:443/test?foo=bar')->assertPortIs('443');
        $this->browserAt('https://www.laravel.com')->assertPortIs('');

        try {
            $this->browserAt('https://www.laravel.com:22')->assertPortIs('21');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Actual port [22] does not equal expected port [21].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_port_is_not(): void
    {
        $this->browserAt('http://www.laravel.com:80/test?foo=bar')->assertPortIsNot('443');
        $this->browserAt('https://www.laravel.com:443/test?foo=bar')->assertPortIsNot('80');
        $this->browserAt('https://www.laravel.com')->assertPortIsNot('22');

        try {
            $this->browserAt('https://www.laravel.com:22')->assertPortIsNot('22');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Port [22] should not equal the actual value.',
                $e->getMessage()
            );
        }
    }

    public function test_assert_path_begins_with(): void
    {
        $this->browserAt('http://www.google.com/test')->assertPathBeginsWith('/tes');

        try {
            $this->browserAt('http://www.google.com/test')->assertPathBeginsWith('test');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Actual path [/test] does not begin with expected path [test].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_path_ends_with(): void
    {
        $this->browserAt('http://www.google.com/test/ending')->assertPathEndsWith('ending');

        try {
            $this->browserAt('http://www.google.com/test/ending')->assertPathEndsWith('/not-the-ending-expected');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Actual path [/test/ending] does not end with expected path [/not-the-ending-expected].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_path_contains(): void
    {
        $this->browserAt('http://www.google.com/admin/test/1/details')->assertPathContains('/test/1/');

        try {
            $this->browserAt('http://www.google.com/admin/test/1/details')->assertPathContains('/test/2/');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Actual path [/admin/test/1/details] does not contain the expected string [/test/2/].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_path_is(): void
    {
        $this->browserAt('/foo')->assertPathIs('/foo');
        $this->browserAt('foo/bar')->assertPathIs('foo/bar');
        $this->browserAt('foo/1/bar')->assertPathIs('foo/*/bar');
        $this->browserAt('foo/1/bar/1')->assertPathIs('foo/*/bar/*');
        $this->browserAt('foo/1/bar/1')->assertPathIs('foo/1/bar/1');

        try {
            $this->browserAt('foo/1/bar/1')->assertPathIs('foo/*/');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Actual path [foo/1/bar/1] does not equal expected path [foo/*/].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_path_is_not(): void
    {
        $this->browserAt('http://www.google.com/test')->assertPathIsNot('test');

        try {
            $this->browserAt('http://www.google.com/test')->assertPathIsNot('/test');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Path [/test] should not equal the actual value.',
                $e->getMessage()
            );
        }
    }

    public function test_assert_fragment_is(): void
    {
        $this->browserAt('http://www.google.com/#baz')->assertFragmentIs('baz');
        $this->browserAt('http://www.google.com/#baz')->assertFragmentIs('*az');

        try {
            $this->browserAt('http://www.google.com/#baz')->assertFragmentIs('bar');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Actual fragment [baz] does not equal expected fragment [bar].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_fragment_begins_with(): void
    {
        $this->browserAt('http://www.google.com/#baz')->assertFragmentBeginsWith('ba');

        try {
            $this->browserAt('http://www.google.com/#baz')->assertFragmentBeginsWith('bz');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Actual fragment [baz] does not begin with expected fragment [bz].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_fragment_is_not(): void
    {
        $this->browserAt('http://www.google.com/#baz')->assertFragmentIsNot('bar');

        try {
            $this->browserAt('http://www.google.com/#baz')->assertFragmentIsNot('baz');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Fragment [baz] should not equal the actual value.',
                $e->getMessage()
            );
        }
    }

    public function test_assert_query_string_has(): void
    {
        $browser = $this->browserAt('http://www.google.com/?foo=bar&items[]=1&items[]=2');

        $browser->assertQueryStringHas('foo');
        $browser->assertQueryStringHas('foo', 'bar');
        $browser->assertQueryStringHas('items', ['1', '2']);

        try {
            $browser->assertQueryStringHas('foo', 'baz');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Query string parameter [foo] had value [bar], but expected [baz].',
                $e->getMessage()
            );
        }

        try {
            $this->browserAt('http://www.google.com')->assertQueryStringHas('foo');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Did not see expected query string in [http://www.google.com].',
                $e->getMessage()
            );
        }
    }

    public function test_assert_query_string_missing(): void
    {
        $this->browserAt('http://www.google.com')->assertQueryStringMissing('foo');
        $this->browserAt('http://www.google.com/?bar=baz')->assertQueryStringMissing('foo');

        try {
            $this->browserAt('http://www.google.com/?foo=bar')->assertQueryStringMissing('foo');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString(
                'Found unexpected query string parameter [foo] in [http://www.google.com/?foo=bar].',
                $e->getMessage()
            );
        }
    }

    private function browserAt(string $url): Browser
    {
        $page = $this->createStub(PageInterface::class);

        $page->method('url')->willReturn($url);

        return new Browser($page);
    }
}
