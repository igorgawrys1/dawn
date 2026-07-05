<?php

declare(strict_types=1);

namespace Dawn\Concerns;

use Dawn\Exceptions\UnsupportedDuskMethod;
use Dawn\Keyboard;
use Illuminate\Support\Arr;
use Playwright\Locator\LocatorInterface;

trait InteractsWithElements
{
    /**
     * All elements matching the given selector.
     *
     * Note: returns Playwright locators, not WebDriver RemoteWebElements.
     *
     * @return list<LocatorInterface>
     */
    public function elements(string $selector): array
    {
        return array_values($this->resolver->all($selector)->all());
    }

    /**
     * The first element matching the given selector, or null.
     *
     * Note: returns a Playwright locator, not a WebDriver RemoteWebElement.
     */
    public function element(string $selector): ?LocatorInterface
    {
        $locator = $this->resolver->all($selector);

        return $locator->count() > 0 ? $locator->first() : null;
    }

    /**
     * Click the link with the given visible text.
     */
    public function clickLink(string $link, string $element = 'a'): static
    {
        $this->linkLocator($link, $element)->first()->click($this->actionOptions());

        return $this;
    }

    /**
     * A locator for visible links with the given text, scope-aware.
     */
    protected function linkLocator(string $link, string $element = 'a'): LocatorInterface
    {
        $text = str_replace(['\\', '"'], ['\\\\', '\\"'], $link);

        return $this->page->locator(
            trim($this->resolver->prefix.' '.$element.':has-text("'.$text.'")').' >> visible=true'
        );
    }

    /**
     * Get or set the value of the element matching the given selector.
     */
    public function value(string $selector, string|int|float|null $value = null): static|string|null
    {
        if ($value === null) {
            return $this->elementValue($this->resolver->format($selector));
        }

        $formatted = $this->resolver->format($selector);

        $this->page->evaluate(
            'document.querySelector('.json_encode($formatted).').value = '.json_encode($value).';'
        );

        return $this;
    }

    /**
     * Get the text of the element matching the given selector.
     */
    public function text(string $selector): string
    {
        return $this->elementInnerText($this->resolver->format($selector));
    }

    /**
     * Get the given attribute of the element matching the given selector.
     */
    public function attribute(string $selector, string $attribute): ?string
    {
        return $this->resolver->resolve($selector)->getAttribute($attribute, $this->actionOptions());
    }

    /**
     * Send the given keys to the element matching the given selector.
     *
     * @param  string|list<string>  ...$keys
     */
    public function keys(string $selector, string|array ...$keys): static
    {
        $this->resolver->resolve($selector)->focus();

        Keyboard::send($this->page, array_values($keys));

        return $this;
    }

    /**
     * Type the given value in the given field, clearing it first.
     */
    public function type(string $field, string|int|float $value): static
    {
        $this->resolver->resolveForTyping($field)->fill((string) $value, $this->actionOptions());

        return $this;
    }

    /**
     * Type slowly, with a per-keystroke delay applied inside the browser.
     */
    public function typeSlowly(string $field, string|int|float $value, int $pause = 100): static
    {
        $locator = $this->resolver->resolveForTyping($field);

        $locator->clear($this->actionOptions());
        $locator->type((string) $value, $this->actionOptions(['delay' => $pause]));

        return $this;
    }

    /**
     * Type the given value in the given field without clearing it.
     */
    public function append(string $field, string|int|float $value): static
    {
        $this->resolver->resolveForTyping($field)->type((string) $value, $this->actionOptions());

        return $this;
    }

    /**
     * Append slowly, with a per-keystroke delay applied inside the browser.
     */
    public function appendSlowly(string $field, string|int|float $value, int $pause = 100): static
    {
        $this->resolver->resolveForTyping($field)->type((string) $value, $this->actionOptions(['delay' => $pause]));

        return $this;
    }

    /**
     * Clear the given field.
     */
    public function clear(string $field): static
    {
        $this->resolver->resolveForTyping($field)->clear($this->actionOptions());

        return $this;
    }

    /**
     * Select the given value or a random enabled option of a select field.
     *
     * @param  string|int|float|bool|array<int, string|int|float|bool>|null  $value
     */
    public function select(string $field, string|int|float|bool|array|null $value = null): static
    {
        $element = $this->resolver->resolveForSelection($field);

        if (func_num_args() === 1) {
            /** @var list<string> $options */
            $options = $element->evaluate(
                'el => Array.from(el.options).filter(o => !o.disabled).map(o => o.value)'
            );

            if ($options !== []) {
                $element->selectOption($options[array_rand($options)], $this->actionOptions());
            }

            return $this;
        }

        $values = array_map(
            static fn (string|int|float|bool $value): string => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
            Arr::wrap($value),
        );

        $element->selectOption($values, $this->actionOptions());

        return $this;
    }

    /**
     * Select the given value of a radio button field.
     */
    public function radio(string $field, string|int|float|bool $value): static
    {
        $value = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        $this->resolver->resolveForRadioSelection($field, $value)->check($this->actionOptions());

        return $this;
    }

    /**
     * Check the given checkbox.
     */
    public function check(string $field, ?string $value = null): static
    {
        $this->resolver->resolveForChecking($field, $value)->check($this->actionOptions());

        return $this;
    }

    /**
     * Uncheck the given checkbox.
     */
    public function uncheck(string $field, ?string $value = null): static
    {
        $this->resolver->resolveForChecking($field, $value)->uncheck($this->actionOptions());

        return $this;
    }

    /**
     * Attach the given file(s) to the given field.
     *
     * @param  string|list<string>  $path
     */
    public function attach(string $field, string|array $path): static
    {
        $this->resolver->resolveForAttachment($field)->setInputFiles($path, $this->actionOptions());

        return $this;
    }

    /**
     * Press the button with the given text or name.
     */
    public function press(string $button): static
    {
        $this->resolver->resolveForButtonPress($button)->click($this->actionOptions());

        return $this;
    }

    /**
     * Press the button and wait for it to be enabled again - the wait runs as
     * a promise inside the browser, anchored to the resolved element.
     */
    public function pressAndWaitFor(string $button, int $seconds = 5): static
    {
        $element = $this->resolver->resolveForButtonPress($button);

        $element->click($this->actionOptions());

        $enabled = $element->evaluate(sprintf(
            <<<'JS'
            el => new Promise(resolve => {
                const deadline = Date.now() + %d;
                const check = () => {
                    if (!el.disabled) return resolve(true);
                    if (Date.now() > deadline) return resolve(false);
                    requestAnimationFrame(check);
                };
                check();
            })
            JS,
            $seconds * 1000,
        ));

        if ($enabled !== true) {
            throw new \Dawn\Exceptions\TimeoutException(
                "Waited {$seconds} seconds for button [{$button}] to be enabled."
            );
        }

        return $this;
    }

    public function drag(string $from, string $to): static
    {
        throw UnsupportedDuskMethod::make('drag');
    }

    public function dragUp(string $selector, int $offset): static
    {
        throw UnsupportedDuskMethod::make('dragUp');
    }

    public function dragDown(string $selector, int $offset): static
    {
        throw UnsupportedDuskMethod::make('dragDown');
    }

    public function dragLeft(string $selector, int $offset): static
    {
        throw UnsupportedDuskMethod::make('dragLeft');
    }

    public function dragRight(string $selector, int $offset): static
    {
        throw UnsupportedDuskMethod::make('dragRight');
    }

    public function dragOffset(string $selector, int $x = 0, int $y = 0): static
    {
        throw UnsupportedDuskMethod::make('dragOffset');
    }

    public function acceptDialog(): static
    {
        throw UnsupportedDuskMethod::make('acceptDialog');
    }

    public function typeInDialog(string $value): static
    {
        throw UnsupportedDuskMethod::make('typeInDialog');
    }

    public function dismissDialog(): static
    {
        throw UnsupportedDuskMethod::make('dismissDialog');
    }
}
