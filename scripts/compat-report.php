<?php

declare(strict_types=1);

/*
 * Measure Dawn's compatibility with the CURRENTLY-INSTALLED laravel/dusk.
 *
 * Reflects every public method the real Laravel\Dusk\Browser defines (across
 * its concern traits), then checks whether Dawn\Browser implements each one
 * without throwing UnsupportedDuskMethod. Emits:
 *   - a human summary + the list of unsupported methods to stdout, and
 *   - a shields.io "endpoint" badge JSON at .github/badges/dusk-compat.json
 *
 * Run in a project where BOTH laravel/dusk and gawrys/dawn are installed.
 */

$autoload = __DIR__.'/../vendor/autoload.php';

if (! is_file($autoload)) {
    fwrite(STDERR, "vendor/autoload.php not found - run composer install first.\n");
    exit(2);
}

require $autoload;

if (! class_exists(Laravel\Dusk\Browser::class) || ! class_exists(Dawn\Browser::class)) {
    fwrite(STDERR, "Both laravel/dusk and gawrys/dawn must be installed to measure compatibility.\n");
    exit(2);
}

$duskReflection = new ReflectionClass(Laravel\Dusk\Browser::class);
$dawnReflection = new ReflectionClass(Dawn\Browser::class);

/** @var list<string> $supported */
$supported = [];
/** @var list<string> $unsupported */
$unsupported = [];
/** @var list<string> $missing */
$missing = [];

foreach ($duskReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    $name = $method->getName();

    // Only count methods Dusk itself defines (skip magic methods and helpers
    // inherited from framework traits like Macroable / Conditionable).
    if (str_starts_with($name, '__')) {
        continue;
    }

    $file = $method->getFileName();

    if ($file === false || ! str_contains(str_replace('\\', '/', $file), '/laravel/dusk/')) {
        continue;
    }

    if (! $dawnReflection->hasMethod($name)) {
        $missing[] = $name;

        continue;
    }

    if (methodThrowsUnsupported($dawnReflection->getMethod($name))) {
        $unsupported[] = $name;

        continue;
    }

    $supported[] = $name;
}

sort($supported);
sort($unsupported);
sort($missing);

$total = count($supported) + count($unsupported) + count($missing);
$percent = $total === 0 ? 0.0 : round(count($supported) / $total * 100, 1);

$duskVersion = installedVersion('laravel/dusk');

echo "Dusk compatibility report\n";
echo "=========================\n";
echo "laravel/dusk version : {$duskVersion}\n";
echo 'Dusk Browser methods : '.$total."\n";
echo '  supported          : '.count($supported)."\n";
echo '  unsupported (throw): '.count($unsupported).($unsupported === [] ? '' : ' ['.implode(', ', $unsupported).']')."\n";
echo '  missing            : '.count($missing).($missing === [] ? '' : ' ['.implode(', ', $missing).']')."\n";
echo "Compatibility        : {$percent}%\n";

$badge = [
    'schemaVersion' => 1,
    'label' => 'dusk compat',
    'message' => rtrim(rtrim((string) $percent, '0'), '.').'%',
    'color' => match (true) {
        $percent >= 95 => 'brightgreen',
        $percent >= 90 => 'green',
        $percent >= 80 => 'yellowgreen',
        $percent >= 70 => 'yellow',
        default => 'orange',
    },
];

$badgeDir = __DIR__.'/../.github/badges';

if (! is_dir($badgeDir)) {
    mkdir($badgeDir, 0755, true);
}

file_put_contents(
    $badgeDir.'/dusk-compat.json',
    json_encode($badge, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
);

echo "Wrote .github/badges/dusk-compat.json\n";

/**
 * Whether the given Dawn method's body throws UnsupportedDuskMethod - i.e. it
 * is a deliberate not-supported stub rather than a real implementation.
 */
function methodThrowsUnsupported(ReflectionMethod $method): bool
{
    $file = $method->getFileName();
    $start = $method->getStartLine();
    $end = $method->getEndLine();

    if ($file === false || $start === false || $end === false) {
        return false;
    }

    $lines = array_slice(file($file) ?: [], $start - 1, $end - $start + 1);

    return str_contains(implode('', $lines), 'UnsupportedDuskMethod');
}

function installedVersion(string $package): string
{
    if (! class_exists(Composer\InstalledVersions::class)) {
        return 'unknown';
    }

    try {
        return (string) Composer\InstalledVersions::getPrettyVersion($package);
    } catch (Throwable) {
        return 'unknown';
    }
}
