# Dusk → Dawn compatibility table

Status legend: ✅ supported · 🔜 planned (throws `Dawn\Exceptions\UnsupportedDuskMethod` until then) · ⛔ cannot map cleanly onto Playwright (throws, with the reason below).

Dawn never fails silently: every unsupported method throws a typed exception naming the method and linking here.

## Navigation & page

| Dusk method | Status | Notes |
|---|---|---|
| `visit` | ✅ | Page-object argument support arrives with Pages (🔜) |
| `visitRoute` | ✅ | |
| `blank` | ✅ | |
| `refresh` / `back` / `forward` | ✅ | |
| `resize` | ✅ | Maps to Playwright viewport |
| `maximize` | ⛔ | Playwright has viewports, not OS windows - use `resize()` |
| `move` | ⛔ | Same reason |
| `fitContent` | 🔜 | |
| `disableFitOnFailure` / `enableFitOnFailure` | ✅ | No-ops (nothing to fit) |
| `screenshot` / `screenshotElement` / `responsiveScreenshots` | ✅ | |
| `storeConsoleLog` / `storeSource` | ✅ | Console captured via Playwright console events |
| `within` / `with` / `elsewhere` / `elsewhereWhenAvailable` | ✅ | |
| `withinFrame` | 🔜 | Will map to `frameLocator` |
| `on` / `onWithoutAssert` / `component` / `onComponent` | 🔜 | Dusk Pages & Components object model |
| `ensurejQueryIsAvailable` | ✅ | No-op - Dawn never needs jQuery |
| `pause` / `pauseIf` / `pauseUnless` | ✅ | Waits inside the browser event loop (`setTimeout` promise), no PHP-side sleep |
| `quit` / `tap` / `dump` / `dd` | ✅ | |
| `tinker` / `stop` | 🔜 | |

## Input & elements

| Dusk method | Status | Notes |
|---|---|---|
| `type` / `typeSlowly` / `append` / `appendSlowly` / `clear` | ✅ | Slow-typing delay runs inside Playwright, not PHP |
| `keys` | ✅ | WebDriverKeys tokens translated to Playwright keys; chords via `press("Shift+X")` |
| `select` | ✅ | Random selection excludes disabled options, like Dusk. Selecting a missing value fails fast (Playwright is stricter than Dusk's silent no-op) |
| `radio` / `check` / `uncheck` | ✅ | |
| `attach` | ✅ | `setInputFiles` |
| `press` / `pressAndWaitFor` | ✅ | Button search order: selector → name → submit value → text |
| `clickLink` | ✅ | Text matching is case-insensitive (Playwright `:has-text`); Dusk's jQuery `:contains` was case-sensitive |
| `value` / `text` / `attribute` | ✅ | |
| `element` / `elements` | ✅ | Return Playwright locators, **not** WebDriver `RemoteWebElement`s |
| `drag` / `dragUp` / `dragDown` / `dragLeft` / `dragRight` / `dragOffset` | 🔜 | |
| `acceptDialog` / `dismissDialog` / `typeInDialog` | 🔜 | Needs the pre-registered dialog-capture bridge |

## Mouse

| Dusk method | Status | Notes |
|---|---|---|
| `click($selector)` | ✅ | `click()` at current cursor position is 🔜 |
| `clickAtXPath` | ✅ | |
| `clickWhenEnabled` / `clickWhenVisible` | ✅ | Playwright's click natively waits for exactly these |
| `doubleClick($selector)` / `rightClick($selector)` / `controlClick($selector)` | ✅ | Cursor-position variants 🔜 |
| `mouseover` | ✅ | |
| `moveMouse` / `clickAtPoint` / `clickAndHold` / `releaseMouse` | 🔜 | |
| `scrollIntoView` / `scrollTo` | ✅ | |

## Waiting

All waits are delegated to Playwright: element waits use native `locator.waitFor()`, and text/URL/script waits install a single in-browser condition promise (the same `requestAnimationFrame` mechanism as Playwright's `waitForFunction`). No PHP-side polling.

| Dusk method | Status | Notes |
|---|---|---|
| `waitFor` / `waitUntilMissing` | ✅ | |
| `waitForText` / `waitForTextIn` / `waitUntilMissingText` | ✅ | Preserves Dusk's case-sensitivity semantics |
| `waitForLink` / `waitForInput` | ✅ | |
| `waitForLocation` / `waitForRoute` | ✅ | Exact Dusk semantics (pathname compare, query ignored), navigation-safe |
| `waitUntilEnabled` / `waitUntilDisabled` | ✅ | |
| `waitUntil` | ✅ | |
| `whenAvailable` | ✅ | |
| `waitForReload` / `clickAndWaitForReload` | ✅ | |
| `waitUsing` | ✅ | **The single exception**: the condition is an arbitrary PHP closure, which Playwright cannot evaluate, so this one method retries on the PHP side (isolated in `Dawn\Support\Waiter`). Prefer the native waits above |
| `waitForDialog` | 🔜 | |
| `waitForEvent` | 🔜 | |
| `waitUntilVue` / `waitUntilVueIsNot` | 🔜 | |

## Assertions

Point-in-time, exactly like Dusk - suites pair `waitFor*` with `assert*`.

| Dusk method | Status |
|---|---|
| `assertTitle` / `assertTitleContains` | ✅ |
| `assertSee` / `assertDontSee` / `assertSeeIn` / `assertDontSeeIn` / `assertSeeAnythingIn` / `assertSeeNothingIn` | ✅ |
| `assertCount` | ✅ |
| `assertScript` | ✅ |
| `assertSourceHas` / `assertSourceMissing` | ✅ |
| `assertSeeLink` / `assertDontSeeLink` / `seeLink` | ✅ |
| `assertInputValue` / `assertInputValueIsNot` / `inputValue` | ✅ |
| `assertInputPresent` / `assertInputMissing` | ✅ |
| `assertChecked` / `assertNotChecked` / `assertIndeterminate` | ✅ |
| `assertRadioSelected` / `assertRadioNotSelected` | ✅ |
| `assertSelected` / `assertNotSelected` / `selected` | ✅ |
| `assertSelectHasOptions` / `assertSelectMissingOptions` / `assertSelectHasOption` / `assertSelectMissingOption` | ✅ |
| `assertValue` / `assertValueIsNot` | ✅ |
| `assertAttribute` / `assertAttributeMissing` / `assertAttributeContains` / `assertAttributeDoesntContain` | ✅ |
| `assertAriaAttribute` / `assertDataAttribute` | ✅ |
| `assertVisible` / `assertPresent` / `assertNotPresent` / `assertMissing` | ✅ |
| `assertEnabled` / `assertDisabled` / `assertButtonEnabled` / `assertButtonDisabled` | ✅ |
| `assertFocused` / `assertNotFocused` | ✅ |
| `assertHasCookie` / `assertHasPlainCookie` / `assertCookieMissing` / `assertPlainCookieMissing` / `assertCookieValue` / `assertPlainCookieValue` | 🔜 |
| `assertDialogOpened` | 🔜 |
| `assertVue` / `assertVueIsNot` / `assertVueContains` / `assertVueDoesntContain` / `assertVueDoesNotContain` / `vueAttribute` | 🔜 |

## URL assertions

| Dusk method | Status |
|---|---|
| `assertUrlIs` / `assertSchemeIs` / `assertSchemeIsNot` / `assertHostIs` / `assertHostIsNot` / `assertPortIs` / `assertPortIsNot` | ✅ |
| `assertPathIs` / `assertPathIsNot` / `assertPathBeginsWith` / `assertPathEndsWith` / `assertPathContains` | ✅ |
| `assertRouteIs` | ✅ |
| `assertQueryStringHas` / `assertQueryStringMissing` | ✅ |
| `assertFragmentIs` / `assertFragmentBeginsWith` / `assertFragmentIsNot` | ✅ |

## Authentication & cookies

| Dusk method | Status | Notes |
|---|---|---|
| `login` / `loginAs` / `logout` | ✅ | Via environment-gated `/_dawn/*` routes, same mechanism as Dusk |
| `assertAuthenticated` / `assertGuest` / `assertAuthenticatedAs` | ✅ | |
| `cookie` / `plainCookie` / `addCookie` / `deleteCookie` | 🔜 | Encrypted-cookie support needs the app's encrypter |

## JavaScript & lifecycle

| Dusk method | Status |
|---|---|
| `script` | ✅ |
| `withKeyboard` | 🔜 |
| `browse` (incl. multiple browsers) | ✅ |

## Known behavioural divergences (all documented, none silent)

1. **Candidate order vs DOM order** - Dusk tries selector candidates in priority order; Dawn compiles them into one CSS list resolved in DOM order. Differs only when several candidates match different elements simultaneously.
2. **`clickLink` / `waitForLink` matching is case-insensitive** (Playwright `:has-text` vs jQuery `:contains`).
3. **`element()` / `elements()` return Playwright locators**, not `RemoteWebElement`s - WebDriver types are deliberately absent.
4. **`select()` with a nonexistent value fails fast** instead of Dusk's silent no-op.
5. **Actions auto-wait up to 5 s** (Dusk's default wait time) for actionability before failing - strictly fewer flakes than Dusk's act-immediately model; assertions remain point-in-time.
