<?php

declare(strict_types=1);

/*
 * Dusk class-name compatibility.
 *
 * Existing Dusk test files reference Laravel\Dusk\Browser in closure type
 * hints. PHP does NOT autoload classes when checking typed parameters, so the
 * Browser alias must exist eagerly - a lazy autoloader would never fire for
 * `function (Browser $browser)`. Base classes (TestCase, ...) are aliased
 * lazily instead, because `extends` does trigger autoloading.
 *
 * If laravel/dusk is installed, the real classes always win and none of these
 * aliases are created.
 */
if (! class_exists(Laravel\Dusk\Browser::class)) {
    class_alias(Dawn\Browser::class, Laravel\Dusk\Browser::class);
}

spl_autoload_register(static function (string $class): void {
    $aliases = [
        'Laravel\\Dusk\\TestCase' => Dawn\TestCase::class,
        'Laravel\\Dusk\\ElementResolver' => Dawn\ElementResolver::class,
        'Laravel\\Dusk\\Dusk' => Dawn\Dawn::class,
    ];

    if (isset($aliases[$class])) {
        class_alias($aliases[$class], $class);
    }
});
