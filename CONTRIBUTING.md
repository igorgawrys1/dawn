# Contributing to Dawn

Thanks for considering a contribution!

## Ground rules (from the project's design constraints)

1. **No WebDriver.** Dawn must never implement `Facebook\WebDriver` interfaces
   or expose a WebDriver endpoint.
2. **No PHP-side waiting.** All waiting is delegated to Playwright (native
   locator waits or in-browser condition promises). The only sanctioned
   exception is `Dawn\Support\Waiter` (backing `waitUsing()`), and CI enforces
   this with a grep gate.
3. **Never approximate silently.** A Dusk method Dawn cannot faithfully
   support throws `UnsupportedDuskMethod` - update COMPATIBILITY.md in the
   same PR.
4. **Dusk semantics are the spec.** When in doubt, read laravel/dusk's source
   and copy its behaviour (including assertion messages). Where behaviour must
   diverge, document it in COMPATIBILITY.md's divergence list.

## Development

```bash
composer install
vendor/bin/playwright-install --browsers   # requires Node.js 20+

composer lint      # pint --test
composer analyse   # phpstan, max level
composer test      # unit + real-browser fixture suite
```

The real-app integration test (used in CI) can be run locally:

```bash
scripts/integration-test.sh '^12.0'
```

## Pull requests

- One logical change per PR; include tests (browser tests must not contain
  sleeps - the fixtures mutate the DOM asynchronously on purpose).
- `composer check` must pass (Pint, PHPStan max, full suite).
- Follow the existing code style: `declare(strict_types=1)`, typed
  signatures, Dusk-verbatim assertion messages.
