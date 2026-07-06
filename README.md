<div align="center">

# 🌅 Dawn

### Run your existing Laravel Dusk suite on Playwright - without rewriting a single test.

[![Latest Version](https://img.shields.io/packagist/v/gawrys/dawn.svg?label=packagist&style=flat-square)](https://packagist.org/packages/gawrys/dawn)
[![Total Downloads](https://img.shields.io/packagist/dt/gawrys/dawn.svg?style=flat-square)](https://packagist.org/packages/gawrys/dawn)
[![CI](https://img.shields.io/github/actions/workflow/status/igorgawrys1/dawn/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/igorgawrys1/dawn/actions/workflows/ci.yml)<br>
[![PHP Version](https://img.shields.io/packagist/php-v/gawrys/dawn.svg?style=flat-square)](https://packagist.org/packages/gawrys/dawn)
[![Laravel](https://img.shields.io/badge/Laravel-10%20|%2011%20|%2012%20|%2013-FF2D20?logo=laravel&style=flat-square)](https://laravel.com)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen?style=flat-square)](phpstan.neon.dist)
[![Dusk compatibility](https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/igorgawrys1/dawn/main/.github/badges/dusk-compat.json&style=flat-square)](COMPATIBILITY.md)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

**[Documentation](https://igorgawrys1.github.io/dawn-docs/)** ·
**[Migration guide](https://igorgawrys1.github.io/dawn-docs/guide/migration)** ·
**[Why it's a game-changer](https://igorgawrys1.github.io/dawn-docs/guide/why-a-game-changer)** ·
**[Compatibility](COMPATIBILITY.md)** ·
**[Changelog](CHANGELOG.md)**

</div>

---

> [!NOTE]
> **Dawn does not swap Dusk's WebDriver driver; it reimplements Dusk's public Browser API on Playwright.**

Your test bodies stay exactly as they are - `visit`, `type`, `press`, `waitFor`,
`assertSee`, `within`, `loginAs`, `@dusk` selectors, all of it - while the browser
underneath is driven by Playwright: native auto-waiting, no ChromeDriver binary to
manage, no Selenium.

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

That test runs **unchanged** under Dawn.

## Contents

- [Why Dawn is a game-changer](#why-dawn-is-a-game-changer)
- [Migrate in 3 steps](#migrate-in-3-steps)
- [Installation](#installation)
- [Configuration](#configuration)
- [Databases](#databases)
- [Compatibility](#compatibility)
- [Roadmap](#roadmap)

## Why Dawn is a game-changer

Dusk's flakiness and ops overhead come almost entirely from **WebDriver**: a
separate ChromeDriver binary you version-match by hand, and PHP-side polling loops
that race the browser. Dawn keeps Dusk's ergonomics and deletes that layer.

| | Dusk (Selenium / ChromeDriver) | **Dawn (Playwright)** |
|---|---|---|
| **Waiting** | PHP-side polling loops | Playwright's native auto-wait |
| **Flakiness** | Actions fire before the DOM is ready | Every action waits for actionability first |
| **Browser setup** | ChromeDriver binary + version drift | `playwright install` - always matched |
| **Browsers** | Chrome | Chromium, Firefox, WebKit (`DAWN_BROWSER`) |
| **Speed** | Selenium wire protocol | Playwright's direct CDP transport |
| **Your tests** | - | **Identical.** Only the base class changes. |

The best part: **there's nothing to learn.** You keep your test suite, your
`@dusk` selectors, your `loginAs()`, your CI artifact paths. You delete the
ChromeDriver plumbing and get a faster, less flaky run for free. → [Read the full
rationale](https://igorgawrys1.github.io/dawn-docs/guide/why-a-game-changer)

## Migrate in 3 steps

```bash
# 1. Swap the package
composer remove laravel/dusk
composer require --dev gawrys/dawn
vendor/bin/playwright-install --browsers
```

```diff
  // 2. tests/DuskTestCase.php - swap the base class, drop the WebDriver plumbing
- use Laravel\Dusk\TestCase as BaseTestCase;
+ use Dawn\TestCase as BaseTestCase;
```

```bash
# 3. Run it
php artisan test --testsuite=Browser
```

Test classes that `use Laravel\Dusk\Browser;` for closure type-hints keep working
as-is - when laravel/dusk is absent, Dawn aliases that class name automatically.
Full walkthrough in the **[migration guide](https://igorgawrys1.github.io/dawn-docs/guide/migration)**.

## Installation

```bash
composer require --dev gawrys/dawn
vendor/bin/playwright-install --browsers
```

**Requirements:** PHP 8.2+ · Laravel 10, 11, 12 or 13 · Node.js 20+ (runs the Playwright engine).

`Dawn\DawnServiceProvider` is auto-discovered and registers the environment-gated
`/_dawn/login`, `/_dawn/logout` and `/_dawn/user` routes that power `loginAs()` /
`logout()` / `assertAuthenticated()` - the same out-of-process mechanism Dusk uses,
never registered in production.

## Configuration

| Env var | Default | Meaning |
|---|---|---|
| `DAWN_BROWSER` | `chromium` | `chromium`, `firefox` or `webkit` |
| `DAWN_HEADLESS` | `true` | Set `false` to watch the browser |

Base URL comes from `config('app.url')` / `APP_URL`, exactly like Dusk. Failure
screenshots and console logs land in `tests/Browser/screenshots` and
`tests/Browser/console` - the same paths as Dusk, so existing CI artifact globs
keep working.

## Databases

`DatabaseMigrations` and `DatabaseTruncation` work unchanged. `RefreshDatabase`
cannot work - the browser talks to your app over HTTP in another process - so Dawn
fails fast with a clear message instead of letting your suite fail mysteriously.

## Compatibility

The full method-by-method table lives in **[COMPATIBILITY.md](COMPATIBILITY.md)**.
Anything not yet implemented throws a typed `Dawn\Exceptions\UnsupportedDuskMethod`
naming the method and linking to the table - Dawn never approximates silently.

Dawn's own suite includes an acceptance class whose test bodies are byte-identical
Dusk tests (including bodies ported from laravel/dusk's own repository), plus
URL-assertion and selector-formatting cases ported verbatim from Dusk's unit suite.

## Roadmap

As of v0.2.0 Dawn covers **~95%** of the Dusk `Browser` API (see the badge - it's
measured automatically against the current upstream Dusk). The only unsupported
methods are the ones that genuinely can't map onto the stack - OS-window
`maximize()`/`move()`, interactive `tinker()`/`stop()`, and JavaScript dialogs
(an engine deadlock, explained in [COMPATIBILITY.md](COMPATIBILITY.md)).

Out of scope by design: multi-browser matrix configuration, visual regression,
device emulation, a Pest bridge.

## Contributing

Issues and PRs welcome - see [CONTRIBUTING.md](CONTRIBUTING.md). The golden rules:
no WebDriver, no PHP-side waiting (CI-enforced), and Dusk's behaviour is the spec.

## License

MIT. Dawn is not affiliated with or endorsed by Laravel; "Dusk" refers to the
laravel/dusk package whose public API Dawn reimplements.
