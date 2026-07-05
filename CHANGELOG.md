# Changelog

All notable changes to `gawrys/dawn` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-07-05

Initial release: run existing Laravel Dusk suites on Playwright by swapping
only the test base class.

### Added

- `Dawn\Browser` - Dusk's public Browser API reimplemented on the
  `playwright-php/playwright` engine (navigation, input, interaction,
  scoping, waiting, assertions; see COMPATIBILITY.md for the full table).
- `Dawn\ElementResolver` - Dusk selector semantics (`@dusk` attributes, page
  element aliases, `within()` scope prefixes, field/button resolution orders)
  compiled to Playwright locators, resolved lazily so native auto-waiting
  applies at action time.
- Waiting delegated to Playwright: element waits via `locator.waitFor()`;
  text/URL/script waits via a single in-browser `requestAnimationFrame`
  condition promise (navigation-safe re-arming). No PHP-side polling -
  except `waitUsing()`, whose arbitrary-PHP-closure contract is served by the
  one documented `Dawn\Support\Waiter` exception.
- `Dawn\TestCase` + `ProvidesBrowser` - DuskTestCase-compatible lifecycle:
  `browse()` (multi-browser capable), persistent primary browser per class,
  failure screenshots and console logs in Dusk's paths, fail-fast guard
  against `RefreshDatabase`.
- `Dawn\DawnServiceProvider` - environment-gated `/_dawn/login|logout|user`
  routes powering `loginAs()`, `logout()`, `assertAuthenticated*()`.
- Dusk class-name compatibility: `Laravel\Dusk\Browser` (and TestCase,
  ElementResolver, Dusk) alias to their Dawn equivalents when laravel/dusk is
  not installed - test files stay byte-identical.
- `Dawn\Keyboard` - WebDriverKeys token & chord translation to Playwright
  keys (`{command}`, `['{shift}', 'x']`, …).
- Typed failure modes: `UnsupportedDuskMethod` (with compatibility-table
  link), `TimeoutException` (Dusk-style messages), `ElementNotFound`.
- Test suite: unit tests (including selector-formatting and URL-assertion
  cases ported verbatim from laravel/dusk's own suite), a real-browser
  fixture suite with zero sleeps, a byte-identical Dusk acceptance class, and
  a real laravel/laravel integration test (`scripts/integration-test.sh`)
  covering Laravel 10/11/12.
- CI: Pint, PHPStan (max), no-sleep guard, browser tests on PHP 8.2–8.4,
  real-app integration matrix across Laravel 10/11/12.

### Known limitations (throw `UnsupportedDuskMethod`; see COMPATIBILITY.md)

- Dusk Pages & Components, dialogs, `withinFrame`, drag & drop, cookie
  helpers/assertions, `assertVue*`, cursor-position mouse variants,
  `maximize()`/`move()` (Playwright has viewports, not OS windows).

[Unreleased]: https://github.com/igorgawrys1/dawn/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/igorgawrys1/dawn/releases/tag/v0.1.0
