<?php

declare(strict_types=1);

namespace Dawn\Tests\Unit;

use Dawn\Keyboard;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class KeyboardTest extends TestCase
{
    public function test_translates_dusk_key_tokens_to_playwright_keys(): void
    {
        $this->assertSame('Enter', Keyboard::translate('{enter}'));
        $this->assertSame('Enter', Keyboard::translate('{return}'));
        $this->assertSame('Meta', Keyboard::translate('{command}'));
        $this->assertSame('Meta', Keyboard::translate('{meta}'));
        $this->assertSame('ArrowLeft', Keyboard::translate('{left}'));
        $this->assertSame('ArrowLeft', Keyboard::translate('{arrow_left}'));
        $this->assertSame('PageDown', Keyboard::translate('{page_down}'));
        $this->assertSame('F5', Keyboard::translate('{f5}'));
        $this->assertSame('Shift', Keyboard::translate('{SHIFT}'));
    }

    public function test_unknown_tokens_throw_with_the_supported_list(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Asserts both the human-facing prefix and that the supported-token
        // list is included in the message.
        $this->expectExceptionMessageMatches('/^Unknown key token \[\{warp\}\]\. Supported tokens: .+enter.+\.$/');

        Keyboard::translate('{warp}');
    }
}
