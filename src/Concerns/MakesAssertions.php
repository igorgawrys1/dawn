<?php

declare(strict_types=1);

namespace Dawn\Concerns;

use Dawn\Exceptions\UnsupportedDuskMethod;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

trait MakesAssertions
{
    /**
     * Assert that the page title equals the given title.
     */
    public function assertTitle(string $title): static
    {
        $actual = $this->page->title();

        PHPUnit::assertEquals(
            $title,
            $actual,
            "Expected title [{$title}] does not equal actual title [{$actual}]."
        );

        return $this;
    }

    /**
     * Assert that the page title contains the given text.
     */
    public function assertTitleContains(string $title): static
    {
        $actual = $this->page->title();

        PHPUnit::assertTrue(
            str_contains($actual, $title),
            "Did not see expected text [{$title}] within title [{$actual}]."
        );

        return $this;
    }

    /**
     * Assert that the given cookie is present.
     */
    public function assertHasCookie(string $name, bool $decrypt = true): static
    {
        $cookie = $decrypt ? $this->cookie($name) : $this->plainCookie($name);

        PHPUnit::assertNotNull($cookie, "Did not find expected cookie [{$name}].");

        return $this;
    }

    /**
     * Assert that the given unencrypted cookie is present.
     */
    public function assertHasPlainCookie(string $name): static
    {
        return $this->assertHasCookie($name, false);
    }

    /**
     * Assert that the given cookie is not present.
     */
    public function assertCookieMissing(string $name, bool $decrypt = true): static
    {
        $cookie = $decrypt ? $this->cookie($name) : $this->plainCookie($name);

        PHPUnit::assertNull($cookie, "Found unexpected cookie [{$name}].");

        return $this;
    }

    /**
     * Assert that the given unencrypted cookie is not present.
     */
    public function assertPlainCookieMissing(string $name): static
    {
        return $this->assertCookieMissing($name, false);
    }

    /**
     * Assert that the given cookie has the given value.
     */
    public function assertCookieValue(string $name, string $value, bool $decrypt = true): static
    {
        $actual = $decrypt ? $this->cookie($name) : $this->plainCookie($name);

        $display = is_scalar($actual) ? (string) $actual : '';

        PHPUnit::assertEquals(
            $value,
            $actual,
            "Cookie [{$name}] had value [{$display}], but expected [{$value}]."
        );

        return $this;
    }

    /**
     * Assert that the given unencrypted cookie has the given value.
     */
    public function assertPlainCookieValue(string $name, string $value): static
    {
        return $this->assertCookieValue($name, $value, false);
    }

    /**
     * Assert that the given text is present within the current scope.
     */
    public function assertSee(string $text, bool $ignoreCase = false): static
    {
        return $this->assertSeeIn('', $text, $ignoreCase);
    }

    /**
     * Assert that the given text is not present within the current scope.
     */
    public function assertDontSee(string $text, bool $ignoreCase = false): static
    {
        return $this->assertDontSeeIn('', $text, $ignoreCase);
    }

    /**
     * Assert that the given text is present within the given selector.
     */
    public function assertSeeIn(string $selector, string $text, bool $ignoreCase = false): static
    {
        if ($text === '') {
            return $this->assertSeeNothingIn($selector);
        }

        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertTrue(
            Str::contains($this->scopedText($selector), $text, $ignoreCase),
            "Did not see expected text [{$text}] within element [{$fullSelector}]."
        );

        return $this;
    }

    /**
     * Assert that the given text is not present within the given selector.
     */
    public function assertDontSeeIn(string $selector, string $text, bool $ignoreCase = false): static
    {
        if ($text === '') {
            return $this->assertSeeAnythingIn($selector);
        }

        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertFalse(
            Str::contains($this->scopedText($selector), $text, $ignoreCase),
            "Saw unexpected text [{$text}] within element [{$fullSelector}]."
        );

        return $this;
    }

    /**
     * Assert that any text is present within the given selector.
     */
    public function assertSeeAnythingIn(string $selector): static
    {
        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertTrue(
            $this->scopedText($selector) !== '',
            "Saw unexpected text [''] within element [{$fullSelector}]."
        );

        return $this;
    }

    /**
     * Assert that no text is present within the given selector.
     */
    public function assertSeeNothingIn(string $selector): static
    {
        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertTrue(
            $this->scopedText($selector) === '',
            "Did not see expected text [''] within element [{$fullSelector}]."
        );

        return $this;
    }

    /**
     * Assert that exactly the given number of elements match the selector.
     */
    public function assertCount(string $selector, int $expected): static
    {
        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertSame(
            $expected,
            $this->resolver->all($selector)->count(),
            "Element [{$fullSelector}] exactly {$expected} times."
        );

        return $this;
    }

    /**
     * Assert that the given JavaScript expression evaluates to the given value.
     */
    public function assertScript(string $expression, mixed $expected = true): static
    {
        $expression = Str::start($expression, 'return ');

        PHPUnit::assertEquals(
            $expected,
            $this->page->evaluate('() => { '.Str::finish($expression, ';').' }'),
            "JavaScript expression [{$expression}] mismatched."
        );

        return $this;
    }

    /**
     * Assert that the given source code is present on the page.
     */
    public function assertSourceHas(string $code): static
    {
        PHPUnit::assertTrue(
            str_contains((string) $this->page->content(), $code),
            "Did not find expected source code [{$code}]."
        );

        return $this;
    }

    /**
     * Assert that the given source code is not present on the page.
     */
    public function assertSourceMissing(string $code): static
    {
        PHPUnit::assertFalse(
            str_contains((string) $this->page->content(), $code),
            "Found unexpected source code [{$code}]."
        );

        return $this;
    }

    /**
     * Assert that a visible link with the given text is present.
     */
    public function assertSeeLink(string $link): static
    {
        $message = $this->resolver->prefix !== 'body'
            ? "Did not see expected link [{$link}] within [{$this->resolver->prefix}]."
            : "Did not see expected link [{$link}].";

        PHPUnit::assertTrue($this->seeLink($link), $message);

        return $this;
    }

    /**
     * Assert that a visible link with the given text is not present.
     */
    public function assertDontSeeLink(string $link): static
    {
        $message = $this->resolver->prefix !== 'body'
            ? "Saw unexpected link [{$link}] within [{$this->resolver->prefix}]."
            : "Saw unexpected link [{$link}].";

        PHPUnit::assertFalse($this->seeLink($link), $message);

        return $this;
    }

    /**
     * Determine whether a visible link with the given text is present.
     */
    public function seeLink(string $link): bool
    {
        return $this->linkLocator($link)->count() > 0;
    }

    /**
     * Assert that the given input field has the given value.
     */
    public function assertInputValue(string $field, string $value): static
    {
        $actual = $this->inputValue($field);

        PHPUnit::assertEquals(
            $value,
            $actual,
            "Expected value [{$value}] for the [{$field}] input does not equal the actual value [{$actual}]."
        );

        return $this;
    }

    /**
     * Assert that the given input field does not have the given value.
     */
    public function assertInputValueIsNot(string $field, string $value): static
    {
        PHPUnit::assertNotEquals(
            $value,
            $this->inputValue($field),
            "Value [{$value}] for the [{$field}] input should not equal the actual value."
        );

        return $this;
    }

    /**
     * Get the value of the given input or text area field.
     */
    public function inputValue(string $field): string
    {
        $target = json_encode($this->resolver->cssForTyping($field));

        $value = $this->page->evaluate(
            "() => { const el = document.querySelector({$target}); if (el === null) return null;"
            ." return ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName) ? String(el.value) : el.innerText; }"
        );

        if (! is_string($value)) {
            throw \Dawn\Exceptions\ElementNotFound::forSelector($this->resolver->format($field));
        }

        return $value;
    }

    /**
     * Assert that an input field with the given name is present.
     */
    public function assertInputPresent(string $field): static
    {
        return $this->assertPresent(
            "input[name='{$field}'], textarea[name='{$field}'], select[name='{$field}']"
        );
    }

    /**
     * Assert that an input field with the given name is not visible.
     */
    public function assertInputMissing(string $field): static
    {
        return $this->assertMissing(
            "input[name='{$field}'], textarea[name='{$field}'], select[name='{$field}']"
        );
    }

    /**
     * Assert that the given checkbox is checked.
     */
    public function assertChecked(string $field, ?string $value = null): static
    {
        PHPUnit::assertTrue(
            $this->resolver->resolveForChecking($field, $value)->isChecked(),
            "Expected checkbox [{$field}] to be checked, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given checkbox is not checked.
     */
    public function assertNotChecked(string $field, ?string $value = null): static
    {
        PHPUnit::assertFalse(
            $this->resolver->resolveForChecking($field, $value)->isChecked(),
            "Checkbox [{$field}] was unexpectedly checked."
        );

        return $this;
    }

    /**
     * Assert that the given checkbox is in an indeterminate state.
     */
    public function assertIndeterminate(string $field, ?string $value = null): static
    {
        $this->assertNotChecked($field, $value);

        PHPUnit::assertTrue(
            $this->resolver->resolveForChecking($field, $value)->evaluate('el => el.indeterminate') === true,
            "Checkbox [{$field}] was not in indeterminate state."
        );

        return $this;
    }

    /**
     * Assert that the given radio field is selected.
     */
    public function assertRadioSelected(string $field, string $value): static
    {
        PHPUnit::assertTrue(
            $this->resolver->resolveForRadioSelection($field, $value)->isChecked(),
            "Expected radio [{$field}] to be selected, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given radio field is not selected.
     */
    public function assertRadioNotSelected(string $field, ?string $value = null): static
    {
        PHPUnit::assertFalse(
            $this->resolver->resolveForRadioSelection($field, $value)->isChecked(),
            "Radio [{$field}] was unexpectedly selected."
        );

        return $this;
    }

    /**
     * Assert that the given select field has the given value selected.
     */
    public function assertSelected(string $field, string|int|float|bool $value): static
    {
        $value = $this->stringableOptionValue($value);

        PHPUnit::assertTrue(
            $this->selected($field, $value),
            "Expected value [{$value}] to be selected for [{$field}], but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given select field does not have the given value selected.
     */
    public function assertNotSelected(string $field, string|int|float|bool $value): static
    {
        $value = $this->stringableOptionValue($value);

        PHPUnit::assertFalse(
            $this->selected($field, $value),
            "Unexpected value [{$value}] selected for [{$field}]."
        );

        return $this;
    }

    /**
     * Assert that the given select field has all of the given options available.
     *
     * @param  list<string>  $values
     */
    public function assertSelectHasOptions(string $field, array $values): static
    {
        $available = $this->selectOptionValues($field);

        PHPUnit::assertCount(
            count($values),
            array_intersect($values, $available),
            'Expected options ['.implode(',', $values)."] for selection field [{$field}] to be available."
        );

        return $this;
    }

    /**
     * Assert that the given select field is missing all of the given options.
     *
     * @param  list<string>  $values
     */
    public function assertSelectMissingOptions(string $field, array $values): static
    {
        PHPUnit::assertCount(
            0,
            array_intersect($values, $this->selectOptionValues($field)),
            'Unexpected options ['.implode(',', $values)."] for selection field [{$field}]."
        );

        return $this;
    }

    /**
     * Assert that the given select field has the given option available.
     */
    public function assertSelectHasOption(string $field, string $value): static
    {
        return $this->assertSelectHasOptions($field, [$value]);
    }

    /**
     * Assert that the given select field is missing the given option.
     */
    public function assertSelectMissingOption(string $field, string $value): static
    {
        return $this->assertSelectMissingOptions($field, [$value]);
    }

    /**
     * Determine whether the given value is selected for the given select field.
     */
    public function selected(string $field, string|int|float|bool $value): bool
    {
        $value = $this->stringableOptionValue($value);

        $selected = $this->resolver->resolveForSelection($field)->evaluate(
            'el => Array.from(el.selectedOptions ?? []).map(o => o.value)'
        );

        return is_array($selected) && in_array($value, $selected, true);
    }

    /**
     * Assert that the element matching the given selector has the given value.
     */
    public function assertValue(string $selector, string $value): static
    {
        $fullSelector = $this->resolver->format($selector);

        $this->ensureElementSupportsValueAttribute($selector, $fullSelector);

        $actual = $this->elementValue($fullSelector);

        PHPUnit::assertEquals(
            $value,
            $actual,
            "Did not see expected value [{$value}] within element [{$fullSelector}]."
        );

        return $this;
    }

    /**
     * Assert that the element matching the given selector does not have the given value.
     */
    public function assertValueIsNot(string $selector, string $value): static
    {
        $fullSelector = $this->resolver->format($selector);

        $this->ensureElementSupportsValueAttribute($selector, $fullSelector);

        PHPUnit::assertNotEquals(
            $value,
            $this->elementValue($fullSelector),
            "Saw unexpected value [{$value}] within element [{$fullSelector}]."
        );

        return $this;
    }

    /**
     * Ensure the element supports the "value" attribute, as Dusk does.
     */
    public function ensureElementSupportsValueAttribute(string $selector, string $fullSelector): void
    {
        $target = json_encode($fullSelector);

        $tagName = $this->page->evaluate(
            "() => document.querySelector({$target})?.tagName.toLowerCase() ?? null"
        );

        PHPUnit::assertTrue(in_array($tagName, [
            'textarea',
            'select',
            'button',
            'input',
            'li',
            'meter',
            'option',
            'param',
            'progress',
        ], true), "This assertion cannot be used with the element [{$fullSelector}].");
    }

    /**
     * Assert that the element matching the given selector has the given attribute value.
     */
    public function assertAttribute(string $selector, string $attribute, string $value): static
    {
        $fullSelector = $this->resolver->format($selector);

        $actual = $this->resolver->resolve($selector)->getAttribute($attribute, $this->actionOptions());

        PHPUnit::assertNotNull(
            $actual,
            "Did not see expected attribute [{$attribute}] within element [{$fullSelector}]."
        );

        PHPUnit::assertEquals(
            $value,
            $actual,
            "Expected '{$attribute}' attribute [{$value}] does not equal actual value [{$actual}]."
        );

        return $this;
    }

    /**
     * Assert that the element matching the given selector is missing the given attribute.
     */
    public function assertAttributeMissing(string $selector, string $attribute): static
    {
        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertNull(
            $this->resolver->resolve($selector)->getAttribute($attribute, $this->actionOptions()),
            "Saw unexpected attribute [{$attribute}] within element [{$fullSelector}]."
        );

        return $this;
    }

    /**
     * Assert that the element's attribute contains the given value.
     */
    public function assertAttributeContains(string $selector, string $attribute, string $value): static
    {
        $fullSelector = $this->resolver->format($selector);

        $actual = $this->resolver->resolve($selector)->getAttribute($attribute, $this->actionOptions());

        PHPUnit::assertNotNull(
            $actual,
            "Did not see expected attribute [{$attribute}] within element [{$fullSelector}]."
        );

        PHPUnit::assertStringContainsString(
            $value,
            $actual,
            "Attribute '{$attribute}' does not contain [{$value}]. Full attribute value was [{$actual}]."
        );

        return $this;
    }

    /**
     * Assert that the element's attribute does not contain the given value.
     */
    public function assertAttributeDoesntContain(string $selector, string $attribute, string $value): static
    {
        $actual = $this->resolver->resolve($selector)->getAttribute($attribute, $this->actionOptions());

        if ($actual === null) {
            return $this;
        }

        PHPUnit::assertStringNotContainsString(
            $value,
            $actual,
            "Attribute '{$attribute}' contains [{$value}]. Full attribute value was [{$actual}]."
        );

        return $this;
    }

    /**
     * Assert that the element has the given value in the given aria attribute.
     */
    public function assertAriaAttribute(string $selector, string $attribute, string $value): static
    {
        return $this->assertAttribute($selector, 'aria-'.$attribute, $value);
    }

    /**
     * Assert that the element has the given value in the given data attribute.
     */
    public function assertDataAttribute(string $selector, string $attribute, string $value): static
    {
        return $this->assertAttribute($selector, 'data-'.$attribute, $value);
    }

    /**
     * Assert that the element matching the given selector is visible.
     */
    public function assertVisible(string $selector): static
    {
        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertTrue(
            $this->resolver->resolve($selector)->isVisible(),
            "Element [{$fullSelector}] is not visible."
        );

        return $this;
    }

    /**
     * Assert that the element matching the given selector is present in the DOM.
     */
    public function assertPresent(string $selector): static
    {
        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertTrue(
            $this->resolver->all($selector)->count() > 0,
            "Element [{$fullSelector}] is not present."
        );

        return $this;
    }

    /**
     * Assert that the element matching the given selector is not present in the DOM.
     */
    public function assertNotPresent(string $selector): static
    {
        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertTrue(
            $this->resolver->all($selector)->count() === 0,
            "Element [{$fullSelector}] is present."
        );

        return $this;
    }

    /**
     * Assert that the element matching the given selector is not visible.
     */
    public function assertMissing(string $selector): static
    {
        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertTrue(
            $this->resolver->resolve($selector)->isHidden(),
            "Saw unexpected element [{$fullSelector}]."
        );

        return $this;
    }

    public function assertDialogOpened(string $message): static
    {
        throw UnsupportedDuskMethod::make('assertDialogOpened');
    }

    /**
     * Assert that the given field is enabled.
     */
    public function assertEnabled(string $field): static
    {
        PHPUnit::assertTrue(
            $this->resolver->resolveForField($field)->isEnabled(),
            "Expected element [{$field}] to be enabled, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given field is disabled.
     */
    public function assertDisabled(string $field): static
    {
        PHPUnit::assertTrue(
            $this->resolver->resolveForField($field)->isDisabled(),
            "Expected element [{$field}] to be disabled, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given button is enabled.
     */
    public function assertButtonEnabled(string $button): static
    {
        PHPUnit::assertTrue(
            $this->resolver->resolveForButtonPress($button)->isEnabled(),
            "Expected button [{$button}] to be enabled, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given button is disabled.
     */
    public function assertButtonDisabled(string $button): static
    {
        PHPUnit::assertTrue(
            $this->resolver->resolveForButtonPress($button)->isDisabled(),
            "Expected button [{$button}] to be disabled, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given field is focused.
     */
    public function assertFocused(string $field): static
    {
        PHPUnit::assertTrue(
            $this->resolver->resolveForField($field)->evaluate('el => el === document.activeElement') === true,
            "Expected element [{$field}] to be focused, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given field is not focused.
     */
    public function assertNotFocused(string $field): static
    {
        PHPUnit::assertTrue(
            $this->resolver->resolveForField($field)->evaluate('el => el === document.activeElement') === false,
            "Expected element [{$field}] not to be focused, but it was."
        );

        return $this;
    }

    public function assertVue(string $key, mixed $value, ?string $componentSelector = null): static
    {
        throw UnsupportedDuskMethod::make('assertVue');
    }

    public function assertVueIsNot(string $key, mixed $value, ?string $componentSelector = null): static
    {
        throw UnsupportedDuskMethod::make('assertVueIsNot');
    }

    public function assertVueContains(string $key, mixed $value, ?string $componentSelector = null): static
    {
        throw UnsupportedDuskMethod::make('assertVueContains');
    }

    public function assertVueDoesntContain(string $key, mixed $value, ?string $componentSelector = null): static
    {
        throw UnsupportedDuskMethod::make('assertVueDoesntContain');
    }

    public function assertVueDoesNotContain(string $key, mixed $value, ?string $componentSelector = null): static
    {
        throw UnsupportedDuskMethod::make('assertVueDoesNotContain');
    }

    public function vueAttribute(string $componentSelector, string $key): mixed
    {
        throw UnsupportedDuskMethod::make('vueAttribute');
    }

    /**
     * The rendered text of the given selector (or the current scope for '').
     */
    protected function scopedText(string $selector): string
    {
        return $this->elementInnerText($this->resolver->format($selector));
    }

    /**
     * The live "value" property of the first element matching the given
     * formatted selector, read point-in-time.
     */
    protected function elementValue(string $formattedSelector): string
    {
        $target = json_encode($formattedSelector);

        $value = $this->page->evaluate(
            "() => { const el = document.querySelector({$target}); return el === null ? null : String(el.value); }"
        );

        if (! is_string($value)) {
            throw \Dawn\Exceptions\ElementNotFound::forSelector($formattedSelector);
        }

        return $value;
    }

    /**
     * The option values available on the given select field.
     *
     * @return list<string>
     */
    protected function selectOptionValues(string $field): array
    {
        $values = $this->resolver->resolveForSelection($field)->evaluate(
            'el => Array.from(el.options ?? []).map(o => o.value)'
        );

        if (! is_array($values)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $value): string => is_scalar($value) ? (string) $value : '',
            $values,
        ));
    }

    /**
     * Cast an option value the way Dusk does (booleans become "1"/"0").
     */
    protected function stringableOptionValue(string|int|float|bool $value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }
}
