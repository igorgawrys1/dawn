<?php

declare(strict_types=1);

namespace Dawn\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CompatAliasTest extends TestCase
{
    public function test_laravel_dusk_browser_resolves_to_dawn_browser(): void
    {
        $this->assertTrue(class_exists(\Laravel\Dusk\Browser::class));
        $this->assertTrue(is_a(\Laravel\Dusk\Browser::class, \Dawn\Browser::class, true));
    }
}
