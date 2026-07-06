<?php

declare(strict_types=1);

/*
 * Dusk class-name compatibility.
 *
 * Existing Dusk test files reference Laravel\Dusk\* class names directly. When
 * laravel/dusk is NOT installed, these aliases point those names at their Dawn
 * equivalents so test bodies stay byte-identical. If laravel/dusk IS installed,
 * the real classes win and none of these aliases are created.
 *
 * Two strategies:
 *
 *   - EAGER for names used as closure parameter type-hints (Browser in
 *     `browse(function (Browser $b) {...})`, Keyboard in
 *     `withKeyboard(function (Keyboard $k) {...})`). PHP does not run
 *     autoloaders when resolving a closure's parameter types, so a lazy alias
 *     would never fire - the alias must already exist.
 *   - LAZY for names referenced via `extends` / `new` (TestCase, Page,
 *     Component, ...), because those trigger autoloading.
 */

// Wrapped in an IIFE so this autoload.files bootstrap does not leak variables
// into the global scope (matching the lazy branch's closure below).
(static function (): void {
    // Dawn\KeyboardActions is the equivalent of Laravel\Dusk\Keyboard, both as
    // the object handed to a withKeyboard() callback and via direct
    // construction: its constructor accepts a Browser (like Dusk's Keyboard) or
    // a Playwright page (PageInterface), so `new Keyboard($browser)` works too.
    $eagerAliases = [
        'Laravel\\Dusk\\Browser' => Dawn\Browser::class,
        'Laravel\\Dusk\\Keyboard' => Dawn\KeyboardActions::class,
    ];

    foreach ($eagerAliases as $duskClass => $dawnClass) {
        if (! class_exists($duskClass)) {
            class_alias($dawnClass, $duskClass);
        }
    }
})();

spl_autoload_register(static function (string $class): void {
    $aliases = [
        'Laravel\\Dusk\\TestCase' => Dawn\TestCase::class,
        'Laravel\\Dusk\\ElementResolver' => Dawn\ElementResolver::class,
        'Laravel\\Dusk\\Dusk' => Dawn\Dawn::class,
        'Laravel\\Dusk\\Page' => Dawn\Page::class,
        'Laravel\\Dusk\\Component' => Dawn\Component::class,
    ];

    if (isset($aliases[$class])) {
        class_alias($aliases[$class], $class);
    }
});
