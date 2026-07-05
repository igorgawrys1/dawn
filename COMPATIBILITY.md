# Dusk → Dawn compatibility table

Status legend: ✅ supported · ⛔ cannot map onto the current stack (throws `Dawn\Exceptions\UnsupportedDuskMethod`, with the reason below).

Dawn never fails silently: every unsupported method throws a typed exception naming the method and linking here.

As of v0.2.0, Dawn implements the **entire** Laravel Dusk `Browser` public API except a small set that cannot be mapped for concrete technical reasons (documented at the bottom). See the auto-measured compatibility badge in the [README](README.md) for the live number against the current upstream Dusk.

## Navigation & page

| Dusk method | Status | Notes |
|---|---|---|
| `visit` (string or Page) | ✅ | Page objects run their `assert()` and register element shortcuts |
| `visitRoute` | ✅ | |
| `blank` | ✅ | |
| `refresh` / `back` / `forward` | ✅ | |
| `resize` | ✅ | Playwright viewport |
| `fitContent` | ✅ | Measures the document and resizes the viewport |
| `disableFitOnFailure` / `enableFitOnFailure` | ✅ | No-ops (nothing to fit) |
| `maximize` | ⛔ | Playwright has viewports, not OS windows — use `resize()` |
| `move` | ⛔ | Same reason (no OS window to move) |
| `screenshot` / `screenshotElement` / `responsiveScreenshots` | ✅ | |
| `storeConsoleLog` / `storeSource` | ✅ | |
| `within` / `with` / `elsewhere` / `elsewhereWhenAvailable` | ✅ | Accept a selector or a Component |
| `withinFrame` | ✅ | Frame-aware resolver via Playwright `frameLocator` |
| `on` / `onWithoutAssert` / `component` / `onComponent` | ✅ | Dusk Pages & Components object model |
| `ensurejQueryIsAvailable` | ✅ | No-op — Dawn never needs jQuery |
| `pause` / `pauseIf` / `pauseUnless` | ✅ | Waits inside the browser event loop, no PHP-side sleep |
| `quit` / `tap` / `dump` / `dd` | ✅ | |
| `tinker` / `stop` | ⛔ | Interactive REPL / halt-for-inspection: only meaningful in a headed, human-attended run, not in Dawn's headless out-of-process model |

## Input & elements

| Dusk method | Status | Notes |
|---|---|---|
| `type` / `typeSlowly` / `append` / `appendSlowly` / `clear` | ✅ | Slow-typing delay runs inside Playwright |
| `keys` / `withKeyboard` | ✅ | WebDriverKeys tokens & chords → Playwright keys |
| `select` | ✅ | Random selection excludes disabled options, like Dusk |
| `radio` / `check` / `uncheck` | ✅ | |
| `attach` | ✅ | |
| `press` / `pressAndWaitFor` | ✅ | |
| `clickLink` | ✅ | Case-insensitive text match (Playwright `:has-text`) |
| `value` (get/set) / `text` / `attribute` | ✅ | |
| `element` / `elements` | ✅ | Return Playwright locators, **not** WebDriver `RemoteWebElement`s |
| `drag` / `dragUp` / `dragDown` / `dragLeft` / `dragRight` / `dragOffset` | ✅ | `dragTo` + `page.mouse()` sequences |
| `acceptDialog` / `dismissDialog` / `typeInDialog` | ⛔ | See "Dialogs" below |

## Mouse

| Dusk method | Status | Notes |
|---|---|---|
| `click` (selector or current cursor) | ✅ | |
| `clickAtPoint` / `clickAtXPath` | ✅ | |
| `doubleClick` / `rightClick` / `controlClick` (selector or cursor) | ✅ | |
| `clickWhenEnabled` / `clickWhenVisible` | ✅ | Playwright's click natively waits for this |
| `mouseover` | ✅ | |
| `moveMouse` / `clickAndHold` / `releaseMouse` | ✅ | `page.mouse()`, with tracked pointer position |
| `scrollIntoView` / `scrollTo` | ✅ | |

## Waiting

All waits are delegated to Playwright (native locator waits + in-browser `requestAnimationFrame` condition promises). No PHP-side polling except the single documented `waitUsing()`.

| Dusk method | Status | Notes |
|---|---|---|
| `waitFor` / `waitUntilMissing` | ✅ | |
| `waitForText` / `waitForTextIn` / `waitUntilMissingText` | ✅ | Preserves Dusk case-sensitivity |
| `waitForLink` / `waitForInput` | ✅ | |
| `waitForLocation` / `waitForRoute` | ✅ | Exact Dusk semantics, navigation-safe |
| `waitUntilEnabled` / `waitUntilDisabled` | ✅ | |
| `waitUntil` (JS) | ✅ | |
| `waitUntilVue` / `waitUntilVueIsNot` | ✅ | |
| `whenAvailable` | ✅ | |
| `waitForReload` / `clickAndWaitForReload` | ✅ | |
| `waitForEvent` | ✅ | One-shot DOM listener + in-browser wait |
| `waitUsing` | ✅ | The single documented PHP-side retry (arbitrary PHP closure) |
| `waitForDialog` | ⛔ | See "Dialogs" below |

## Assertions

Point-in-time, exactly like Dusk.

**✅ Supported (all of them):** `assertTitle`, `assertTitleContains`, `assertSee`, `assertDontSee`, `assertSeeIn`, `assertDontSeeIn`, `assertSeeAnythingIn`, `assertSeeNothingIn`, `assertCount`, `assertScript`, `assertSourceHas`, `assertSourceMissing`, `assertSeeLink`, `assertDontSeeLink`, `assertInputValue`, `assertInputValueIsNot`, `assertInputPresent`, `assertInputMissing`, `assertChecked`, `assertNotChecked`, `assertIndeterminate`, `assertRadioSelected`, `assertRadioNotSelected`, `assertSelected`, `assertNotSelected`, `assertSelectHasOptions`, `assertSelectMissingOptions`, `assertSelectHasOption`, `assertSelectMissingOption`, `assertValue`, `assertValueIsNot`, `assertAttribute`, `assertAttributeMissing`, `assertAttributeContains`, `assertAttributeDoesntContain`, `assertAriaAttribute`, `assertDataAttribute`, `assertVisible`, `assertPresent`, `assertNotPresent`, `assertMissing`, `assertEnabled`, `assertDisabled`, `assertButtonEnabled`, `assertButtonDisabled`, `assertFocused`, `assertNotFocused`, all URL assertions, cookie assertions, Vue assertions, and authentication assertions.

**⛔ `assertDialogOpened`** — see "Dialogs" below.

## Authentication, cookies & JS

| Dusk method | Status | Notes |
|---|---|---|
| `login` / `loginAs` / `logout` | ✅ | Environment-gated `/_dawn/*` routes |
| `cookie` / `plainCookie` / `addCookie` / `deleteCookie` | ✅ | Encrypted cookies use Laravel's `Crypt` + `CookieValuePrefix`, like Dusk |
| `script` | ✅ | |
| `vueAttribute` | ✅ | Reads Vue 2 (`__vue__`) and Vue 3 (`__vueParentComponent`) internals |

## What genuinely cannot map (the honest ceiling)

### OS windows — `maximize()`, `move()`
Playwright automates via **viewports** (often headless); there is no OS-level browser window to maximize or reposition. `resize()` covers real needs. A no-op would be a lie, so these throw.

### Interactive dev helpers — `tinker()`, `stop()`
These pause a **headed** run so a human can poke around (PsySH REPL / halt). Dawn runs the browser out-of-process and headless-by-default for automated suites; there is no attended session to drop into. They throw with that explanation.

### JavaScript dialogs — `acceptDialog()`, `dismissDialog()`, `typeInDialog()`, `waitForDialog()`, `assertDialogOpened()`
This is an engine constraint, not a Dawn design choice. The `playwright-php/playwright` engine **always** registers a dialog listener on every page, so Playwright never auto-dismisses; the dialog stays open until explicitly accepted/dismissed. Combined with the engine's **synchronous** JSON-RPC transport, the action that opens a blocking `alert()`/`confirm()`/`prompt()` deadlocks: the triggering call blocks waiting for its own response, which cannot arrive until the dialog is handled — and PHP cannot handle it while blocked on that call. Dawn detects and dismisses stray dialogs during failure capture (so one blocked test can't cascade), but cannot let a test *drive* them. This would require upstream engine support (an async transport, or an auto-accept/dismiss command).

### WebDriver element types
`element()` / `elements()` return Playwright locators, never `Facebook\WebDriver\RemoteWebElement`. A suite that calls WebDriver-specific methods on a returned element cannot work — by design, since "no WebDriver" is a core Dawn constraint.

## Behavioural divergences (documented, never silent)

1. **Candidate order vs DOM order** — Dusk tries selector candidates in priority order; Dawn compiles them into one CSS list resolved in DOM order. Differs only when several candidates match different elements simultaneously.
2. **`clickLink` / `waitForLink` matching is case-insensitive** (Playwright `:has-text` vs jQuery `:contains`).
3. **`select()` with a nonexistent value fails fast** instead of Dusk's silent no-op.
4. **Actions auto-wait up to 5 s** (Dusk's default wait time) for actionability before failing — strictly fewer flakes; assertions remain point-in-time.
