<?php

declare(strict_types=1);

namespace Dawn\Tests\Unit;

use Dawn\Dawn;
use Dawn\ElementResolver;
use PHPUnit\Framework\TestCase;
use Playwright\Page\PageInterface;

final class ElementResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Dawn::$selectorHtmlAttribute = 'dusk';

        parent::tearDown();
    }

    public function test_format_prefixes_with_the_scope(): void
    {
        $resolver = $this->resolver();

        $this->assertSame('body .login-form', $resolver->format('.login-form'));
        $this->assertSame('body', $resolver->format(''));
    }

    public function test_format_translates_dusk_attribute_selectors(): void
    {
        $resolver = $this->resolver();

        $this->assertSame('body [dusk="login-button"]', $resolver->format('@login-button'));
        $this->assertSame('body [dusk="table"] tr', $resolver->format('@table tr'));
    }

    public function test_format_honours_a_custom_selector_attribute(): void
    {
        Dawn::selectorHtmlAttribute('data-testid');

        $this->assertSame('body [data-testid="cta"]', $this->resolver()->format('@cta'));
    }

    public function test_format_applies_page_element_aliases_before_translation(): void
    {
        $resolver = $this->resolver();

        $resolver->pageElements([
            '@email' => 'input#email-address',
            '@email-verified' => 'span.verified',
        ]);

        $this->assertSame('body input#email-address', $resolver->format('@email'));
        $this->assertSame('body span.verified', $resolver->format('@email-verified'));
        $this->assertSame('body [dusk="other"]', $resolver->format('@other'));
    }

    public function test_nested_scopes_compose_through_format(): void
    {
        $outer = $this->resolver();

        $inner = new ElementResolver($this->page(), $outer->format('.modal'));

        $this->assertSame('body .modal .confirm', $inner->format('.confirm'));
    }

    /**
     * Ported verbatim from laravel/dusk's own
     * tests/Unit/ElementResolverTest::test_format_correctly_formats_selectors
     * (8.x) - only the resolver construction differs.
     */
    public function test_format_correctly_formats_selectors(): void
    {
        $resolver = new ElementResolver($this->page());
        $this->assertSame('body #modal', $resolver->format('#modal'));

        $resolver = new ElementResolver($this->page(), 'prefix');
        $this->assertSame('prefix #modal', $resolver->format('#modal'));

        $resolver = new ElementResolver($this->page(), 'prefix');
        $resolver->pageElements(['@modal' => '#modal']);
        $this->assertSame('prefix #modal', $resolver->format('@modal'));

        $resolver = new ElementResolver($this->page(), 'prefix');
        $resolver->pageElements([
            '@modal' => '#first',
            '@modal-second' => '#second',
        ]);
        $this->assertSame('prefix #first', $resolver->format('@modal'));
        $this->assertSame('prefix #second', $resolver->format('@modal-second'));
        $this->assertSame('prefix #first-third', $resolver->format('@modal-third'));
        $this->assertSame('prefix [dusk="missing-element"]', $resolver->format('@missing-element'));
        $this->assertSame('prefix [dusk="missing-element"] > div', $resolver->format('@missing-element > div'));
    }

    /**
     * Ported verbatim from laravel/dusk's own test suite (8.x).
     */
    public function test_format_does_not_capture_closing_parenthesis_in_dusk_selector(): void
    {
        $resolver = new ElementResolver($this->page(), 'prefix');

        $this->assertSame(
            'prefix [dusk="products"] div:nth-child(2 of [dusk="product"])',
            $resolver->format('@products div:nth-child(2 of @product)')
        );
    }

    public function test_radio_selection_requires_a_value_for_non_id_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No value was provided for radio button [plan].');

        $this->resolver()->resolveForRadioSelection('plan');
    }

    /**
     * CSS escapes use backslashes (Tailwind's `.md\:flex`, `[name="a\:b"]`), so
     * such selectors must qualify as plausible; malformed ones must not.
     *
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function plausibleSelectors(): array
    {
        return [
            'plain label' => ['Save', true],
            'id' => ['#email', true],
            'attribute' => ['[dusk="login"]', true],
            'tailwind escaped colon class' => ['.md\\:flex', true],
            'attribute with escaped colon' => ['[name="a\\:b"]', true],
            'form-array name' => ['tags[]', false],
            'label with parenthesis' => ['Save (draft)', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('plausibleSelectors')]
    public function test_plausible_css_selector_detection(string $value, bool $expected): void
    {
        $method = new \ReflectionMethod(ElementResolver::class, 'isPlausibleCssSelector');

        $this->assertSame($expected, $method->invoke($this->resolver(), $value));
    }

    private function resolver(): ElementResolver
    {
        return new ElementResolver($this->page());
    }

    private function page(): PageInterface
    {
        return $this->createStub(PageInterface::class);
    }
}
