<?php

declare(strict_types=1);

namespace Dawn;

use InvalidArgumentException;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;

/**
 * Translates Dusk's ElementResolver semantics onto Playwright locators.
 *
 * Selectors are resolved lazily: this class only builds locators, it never
 * queries the DOM itself. Playwright's native auto-waiting therefore applies
 * at action time, and no find-then-act race exists.
 *
 * Known, documented divergence from Dusk: where Dusk tries a list of candidate
 * selectors in priority order and takes the first that yields an element, Dawn
 * compiles the candidates into a single CSS selector list, which Playwright
 * resolves in DOM order. The two only differ when several candidates match
 * different elements at once (e.g. an element with id "email" and another with
 * name "email" in the same document).
 */
class ElementResolver
{
    /**
     * Shorthand element aliases for the current page object, mapping e.g.
     * "@email" to a full selector. Mirrors Dusk's page elements() support.
     *
     * @var array<string, string>
     */
    public array $elements = [];

    public function __construct(
        protected PageInterface $page,
        public string $prefix = 'body',
    ) {
        $this->prefix = trim($prefix);
    }

    /**
     * Set the page-object element shorthands.
     *
     * @param  array<string, string>  $elements
     */
    public function pageElements(array $elements): static
    {
        $this->elements = $elements;

        return $this;
    }

    /**
     * Format the given selector as Dusk does: apply page-element aliases,
     * translate "@name" into the dusk HTML attribute selector, and prepend the
     * current scope prefix.
     */
    public function format(string $selector): string
    {
        $sortedElements = $this->elements;

        uksort($sortedElements, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $originalSelector = $selector;

        $selector = str_replace(
            array_keys($sortedElements), array_values($sortedElements), $selector
        );

        if (str_starts_with($selector, '@') && $selector === $originalSelector) {
            $formatted = preg_replace(
                '/@([^\s\)]+)/',
                '['.Dawn::$selectorHtmlAttribute.'="$1"]',
                $selector
            );

            $selector = $formatted ?? $selector;
        }

        return trim($this->prefix.' '.$selector);
    }

    /**
     * A locator for the first element matching the given Dusk selector.
     */
    public function resolve(string $selector): LocatorInterface
    {
        return $this->all($selector)->first();
    }

    /**
     * A locator matching every element for the given Dusk selector.
     */
    public function all(string $selector): LocatorInterface
    {
        return $this->page->locator($this->format($selector));
    }

    /**
     * A locator for the current scope element itself.
     */
    public function scope(): LocatorInterface
    {
        return $this->page->locator($this->prefix)->first();
    }

    /**
     * Resolve the locator for a given input "field" (Dusk's type/append/clear).
     */
    public function resolveForTyping(string $field): LocatorInterface
    {
        return $this->firstMatch($this->typingCandidates($field));
    }

    /**
     * The CSS selector list used to resolve a typing "field" - also valid for
     * document.querySelector(), which matches the same first element.
     */
    public function cssForTyping(string $field): string
    {
        return implode(', ', array_unique($this->typingCandidates($field)));
    }

    /**
     * @return list<string>
     */
    protected function typingCandidates(string $field): array
    {
        return [
            ...$this->idCandidate($field),
            $this->prefixed("input[name={$this->quote($field)}]"),
            $this->prefixed("textarea[name={$this->quote($field)}]"),
            ...$this->rawSelectorCandidate($field),
        ];
    }

    /**
     * Resolve the locator for a given select "field".
     */
    public function resolveForSelection(string $field): LocatorInterface
    {
        return $this->firstMatch([
            ...$this->idCandidate($field),
            $this->prefixed("select[name={$this->quote($field)}]"),
            ...$this->rawSelectorCandidate($field),
        ]);
    }

    /**
     * Resolve the locator for a given radio "field" / value pair.
     */
    public function resolveForRadioSelection(string $field, ?string $value = null): LocatorInterface
    {
        if (($id = $this->idCandidate($field)) !== []) {
            return $this->firstMatch($id);
        }

        if ($value === null) {
            throw new InvalidArgumentException(
                "No value was provided for radio button [{$field}]."
            );
        }

        return $this->firstMatch([
            $this->prefixed("input[type=radio][name={$this->quote($field)}][value={$this->quote($value)}]"),
            ...$this->rawSelectorCandidate($field),
        ]);
    }

    /**
     * Resolve the locator for a given checkbox "field".
     */
    public function resolveForChecking(string $field, ?string $value = null): LocatorInterface
    {
        $selector = 'input[type=checkbox][name='.$this->quote($field).']';

        if ($value !== null) {
            $selector .= '[value='.$this->quote($value).']';
        }

        return $this->firstMatch([
            ...$this->idCandidate($field),
            $this->prefixed($selector),
            ...$this->rawSelectorCandidate($field),
        ]);
    }

    /**
     * Resolve the locator for a given file "field".
     */
    public function resolveForAttachment(string $field): LocatorInterface
    {
        return $this->firstMatch([
            ...$this->idCandidate($field),
            $this->prefixed("input[type=file][name={$this->quote($field)}]"),
            ...$this->rawSelectorCandidate($field),
        ]);
    }

    /**
     * Resolve the locator for a given generic "field".
     */
    public function resolveForField(string $field): LocatorInterface
    {
        return $this->firstMatch([
            ...$this->idCandidate($field),
            $this->prefixed("input[name={$this->quote($field)}]"),
            $this->prefixed("textarea[name={$this->quote($field)}]"),
            $this->prefixed("select[name={$this->quote($field)}]"),
            $this->prefixed("button[name={$this->quote($field)}]"),
            ...$this->rawSelectorCandidate($field),
        ]);
    }

    /**
     * Resolve the locator for a button, following Dusk's search order:
     * as a selector, by name, by submit value, and finally by visible text.
     */
    public function resolveForButtonPress(string $button): LocatorInterface
    {
        $candidates = [];

        if ($this->isPlausibleCssSelector($button)) {
            $candidates[] = $this->format($button);
        }

        $candidates[] = $this->prefixed("input[type=submit][name={$this->quote($button)}]");
        $candidates[] = $this->prefixed("input[type=button][value={$this->quote($button)}]");
        $candidates[] = $this->prefixed("button[name={$this->quote($button)}]");
        $candidates[] = $this->prefixed("input[type=submit][value={$this->quote($button)}]");
        $candidates[] = $this->prefixed('button:text-matches('.$this->quoteRegex($button).')');

        return $this->firstMatch($candidates);
    }

    /**
     * Combine formatted selector candidates into one locator and take the
     * first match. See the class docblock for the DOM-order caveat.
     *
     * @param  list<string>  $candidates
     */
    protected function firstMatch(array $candidates): LocatorInterface
    {
        return $this->page->locator(implode(', ', array_unique($candidates)))->first();
    }

    /**
     * Dusk resolves "#id" fields against the whole document, bypassing the
     * scope prefix - replicate that as an unprefixed candidate.
     *
     * @return list<string>
     */
    protected function idCandidate(string $field): array
    {
        return preg_match('/^#[\w\-:]+$/', $field) === 1 ? [$field] : [];
    }

    protected function prefixed(string $selector): string
    {
        return trim($this->prefix.' '.$selector);
    }

    /**
     * Quote a value for use inside a CSS attribute selector.
     */
    protected function quote(string $value): string
    {
        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $value)."'";
    }

    /**
     * Quote a value as a case-sensitive "contains" regex string argument for
     * Playwright's :text-matches() pseudo-class.
     */
    protected function quoteRegex(string $value): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], preg_quote($value)).'"';
    }

    /**
     * The raw field/button string tried as a selector in its own right, as
     * Dusk does last - included only when it can participate in a CSS
     * selector list without invalidating it (Dusk instead catches the
     * invalid-selector exception per candidate, which a combined list
     * cannot do). Form-style names such as "tags[]" are not valid CSS and
     * are excluded.
     *
     * @return list<string>
     */
    protected function rawSelectorCandidate(string $field): array
    {
        return $this->isPlausibleCssSelector($field) ? [$this->format($field)] : [];
    }

    /**
     * Whether the string can safely participate in a CSS selector list.
     * Plain button labels like "Save" parse as (non-matching) tag selectors
     * and are harmless; labels with quotes, parentheses, or malformed
     * attribute brackets would invalidate the whole selector list.
     */
    protected function isPlausibleCssSelector(string $value): bool
    {
        return preg_match('/^[\w\s\-.#\[\]=@:>*+~\'"^$]+$/', $value) === 1
            && preg_match('/\[(?![a-zA-Z_-])/', $value) !== 1
            && substr_count($value, '"') % 2 === 0
            && substr_count($value, "'") % 2 === 0;
    }
}
