# Dawn

<p>
    <a href="https://packagist.org/packages/gawrys/dawn"><img src="https://img.shields.io/packagist/v/gawrys/dawn.svg?label=packagist" alt="Latest Version on Packagist"></a>
    <a href="https://packagist.org/packages/gawrys/dawn"><img src="https://img.shields.io/packagist/dt/gawrys/dawn.svg" alt="Total Downloads"></a>
    <a href="https://github.com/igorgawrys1/dawn/actions/workflows/ci.yml"><img src="https://img.shields.io/github/actions/workflow/status/igorgawrys1/dawn/ci.yml?branch=main&label=CI" alt="CI Status"></a>
    <a href="https://packagist.org/packages/gawrys/dawn"><img src="https://img.shields.io/packagist/php-v/gawrys/dawn.svg" alt="PHP Version"></a>
    <img src="https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-FF2D20?logo=laravel" alt="Laravel 10|11|12">
    <img src="https://img.shields.io/badge/PHPStan-max-brightgreen" alt="PHPStan max level">
    <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License: MIT"></a>
</p>

**Run your existing Laravel Dusk test suite on Playwright - without rewriting your tests.**

📚 **Documentation: <https://igorgawrys1.github.io/dawn-docs/>** · [Migration guide](https://igorgawrys1.github.io/dawn-docs/guide/migration) · [Compatibility table](COMPATIBILITY.md) · [Changelog](CHANGELOG.md)

Dawn does not swap Dusk's WebDriver driver; it reimplements Dusk's public
Browser API on Playwright.

Your test bodies stay exactly as they are - `visit`, `type`, `press`,
`waitFor`, `assertSee`, `within`, `loginAs`, `@dusk-selectors`, all of it -
while the browser underneath is driven by Playwright's engine: native
auto-waiting, no ChromeDriver binary management, no Selenium.

```php
$this->browse(function (Browser $browser) {
    $browser->visit('/login')
        ->type('email', 'taylor@laravel.com')
        ->type('password', 'secret')
        ->press('Login')
        ->waitForLocation('/dashboard')
        ->assertSee('Welcome back');
});
```

That test runs unchanged under Dawn.

## Migration (3 lines)

1. `composer remove laravel/dusk && composer require --dev gawrys/dawn && vendor/bin/playwright-install --browsers`
2. In `tests/DuskTestCase.php`, extend `Dawn\TestCase` instead of `Laravel\Dusk\TestCase` (and delete the `driver()` / `prepare()` WebDriver plumbing).
3. `php artisan test --testsuite=Browser` - done.

Test classes that import `Laravel\Dusk\Browser` for closure type-hints keep
working as-is: when laravel/dusk is not installed, Dawn aliases that class
name to `Dawn\Browser` automatically.

## Why Dawn

| | Dusk (Selenium/ChromeDriver) | Dawn (Playwright) |
|---|---|---|
| Waiting | PHP-side polling loops | Playwright native auto-wait |
| Browser management | ChromeDriver binary, version drift | `playwright install`, always matched |
| Actions | Act immediately, fail on race | Auto-wait for actionability (up to Dusk's 5 s default) |
| Assertions | Point-in-time | Point-in-time (identical semantics) |
| Browsers | Chrome | Chromium, Firefox, WebKit (`DAWN_BROWSER`) |

Dawn is **not**:

- a WebDriver driver swap - no `Facebook\WebDriver` interfaces anywhere;
- a poller - no `sleep()`, no DOM-polling loops; element waits are Playwright
  `locator.waitFor()`, and text/URL/script waits run as a single in-browser
  condition promise (the same `requestAnimationFrame` mechanism Playwright's
  own `waitForFunction` uses). The one exception is `waitUsing()`, whose
  contract is an arbitrary **PHP** closure - it is implemented in one
  documented, isolated class;
- a Pest plugin - class-based, PHPUnit-native, mirrors `DuskTestCase`;
- an HTTP bridge - your tests stay in PHP.

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- Node.js 20+ (used by the Playwright engine; installed browsers via
  `vendor/bin/playwright-install --browsers`)

## Installation

```bash
composer require --dev gawrys/dawn
vendor/bin/playwright-install --browsers
```

The `Dawn\DawnServiceProvider` is auto-discovered and registers the
environment-gated `/_dawn/login`, `/_dawn/logout` and `/_dawn/user` routes
that power `loginAs()` / `logout()` / `assertAuthenticated()` - the same
out-of-process mechanism Dusk uses (never registered in production).

## Configuration

| Env var | Default | Meaning |
|---|---|---|
| `DAWN_BROWSER` | `chromium` | `chromium`, `firefox` or `webkit` |
| `DAWN_HEADLESS` | `true` | Set `false` to watch the browser |

Base URL comes from `config('app.url')` / `APP_URL`, exactly like Dusk.
Screenshots and console logs on failure land in `tests/Browser/screenshots`
and `tests/Browser/console` - the same paths as Dusk, so existing CI artifact
globs keep working.

## Databases

`DatabaseMigrations` and `DatabaseTruncation` work unchanged.
`RefreshDatabase` cannot work (the browser talks to your app over HTTP in
another process) - Dawn fails fast with a clear message instead of letting
your suite fail mysteriously.

## Documentation

Full documentation - getting started, migration, how waiting works, selector
semantics, configuration, architecture - lives at
**<https://igorgawrys1.github.io/dawn-docs/>**.

## Compatibility

The full method-by-method table lives in [COMPATIBILITY.md](COMPATIBILITY.md).
Anything not yet implemented throws a typed
`Dawn\Exceptions\UnsupportedDuskMethod` naming the method and linking to the
table - Dawn never approximates silently.

Dawn's own test suite includes an acceptance class whose test bodies are
byte-identical Dusk tests (including bodies ported from laravel/dusk's own
repository), plus URL-assertion and selector-formatting cases ported verbatim
from Dusk's unit suite.

## Roadmap (not in v1)

Dusk Pages & Components, dialogs, frames (`withinFrame`), drag & drop,
encrypted-cookie helpers, `assertVue*`, multi-browser matrix configuration,
visual regression, device emulation, a Pest bridge.

## License

MIT. Dawn is not affiliated with or endorsed by Laravel; "Dusk" refers to the
laravel/dusk package whose public API Dawn reimplements.
