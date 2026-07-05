## What & why

<!-- What does this PR change, and what problem does it solve? -->

## Checklist

- [ ] `composer check` passes (Pint, PHPStan max, full suite)
- [ ] No PHP-side sleeping or polling added outside `Dawn\Support` (CI enforces this)
- [ ] No `Facebook\WebDriver` types introduced
- [ ] New/changed Dusk-mapped behaviour matches laravel/dusk semantics (messages included) - or the divergence is documented
- [ ] COMPATIBILITY.md updated if any method's status changed
- [ ] CHANGELOG.md updated under Unreleased
