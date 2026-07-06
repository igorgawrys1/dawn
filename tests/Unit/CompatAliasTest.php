<?php

declare(strict_types=1);

namespace Dawn\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CompatAliasTest extends TestCase
{
    /**
     * Every Laravel\Dusk\* class name a Dusk test body can reference must
     * resolve to its Dawn equivalent when laravel/dusk is not installed.
     *
     * @return array<string, array{0: class-string, 1: class-string}>
     */
    public static function aliases(): array
    {
        return [
            'Browser' => [\Laravel\Dusk\Browser::class, \Dawn\Browser::class],
            'TestCase' => [\Laravel\Dusk\TestCase::class, \Dawn\TestCase::class],
            'ElementResolver' => [\Laravel\Dusk\ElementResolver::class, \Dawn\ElementResolver::class],
            'Page' => [\Laravel\Dusk\Page::class, \Dawn\Page::class],
            'Component' => [\Laravel\Dusk\Component::class, \Dawn\Component::class],
            'Keyboard' => [\Laravel\Dusk\Keyboard::class, \Dawn\KeyboardActions::class],
        ];
    }

    /**
     * @param  class-string  $duskClass
     * @param  class-string  $dawnClass
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('aliases')]
    public function test_dusk_class_resolves_to_dawn_equivalent(string $duskClass, string $dawnClass): void
    {
        $this->assertTrue(class_exists($duskClass), "{$duskClass} is not aliased.");
        $this->assertTrue(is_a($duskClass, $dawnClass, true), "{$duskClass} does not resolve to {$dawnClass}.");
    }

    /**
     * A Dusk page object (extends Laravel\Dusk\Page) must work as a Dawn page.
     */
    public function test_a_dusk_page_object_is_usable(): void
    {
        $page = new class extends \Laravel\Dusk\Page
        {
            public function url(): string
            {
                return '/dashboard';
            }
        };

        $this->assertInstanceOf(\Dawn\Page::class, $page);
        $this->assertSame('/dashboard', $page->url());
    }

    /**
     * A Dusk component (extends Laravel\Dusk\Component) must work as a Dawn component.
     */
    public function test_a_dusk_component_is_usable(): void
    {
        $component = new class extends \Laravel\Dusk\Component
        {
            public function selector(): string
            {
                return '@panel';
            }
        };

        $this->assertInstanceOf(\Dawn\Component::class, $component);
        $this->assertSame('@panel', $component->selector());
    }

    /**
     * Dusk's Keyboard is a plain public class that can be constructed directly
     * with a Browser; Dawn supports that as well as page-based construction.
     */
    public function test_dusk_keyboard_can_be_constructed_directly(): void
    {
        $page = $this->createStub(\Playwright\Page\PageInterface::class);
        $browser = new \Dawn\Browser($page);

        $this->assertInstanceOf(\Dawn\KeyboardActions::class, new \Laravel\Dusk\Keyboard($browser));
        $this->assertInstanceOf(\Dawn\KeyboardActions::class, new \Laravel\Dusk\Keyboard($page));
    }
}
