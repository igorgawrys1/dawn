<?php

declare(strict_types=1);

namespace Dawn\Concerns;

trait InteractsWithMouse
{
    /**
     * The last known mouse position, tracked so that Dusk's offset-based and
     * cursor-position mouse APIs (which WebDriver expresses relative to the
     * current pointer) translate onto Playwright's absolute mouse.
     */
    protected float $mouseX = 0.0;

    protected float $mouseY = 0.0;

    /**
     * Move the mouse over the element matching the given selector.
     */
    public function mouseover(string $selector): static
    {
        $this->resolver->resolve($selector)->hover($this->actionOptions());

        [$this->mouseX, $this->mouseY] = $this->elementCenter($selector);

        return $this;
    }

    /**
     * Move the mouse by the given offset from its current position.
     */
    public function moveMouse(float $xOffset, float $yOffset): static
    {
        $this->mouseX += $xOffset;
        $this->mouseY += $yOffset;

        $this->page->mouse()->move($this->mouseX, $this->mouseY);

        return $this;
    }

    /**
     * Click the element matching the given selector, or - when no selector is
     * given - at the current mouse position.
     */
    public function click(?string $selector = null): static
    {
        if ($selector === null) {
            $this->page->mouse()->click($this->mouseX, $this->mouseY);

            return $this;
        }

        $this->resolver->resolve($selector)->click($this->actionOptions());

        return $this;
    }

    /**
     * Click at the given viewport coordinates.
     */
    public function clickAtPoint(float $x, float $y): static
    {
        $this->page->mouse()->click($x, $y);

        $this->mouseX = $x;
        $this->mouseY = $y;

        return $this;
    }

    /**
     * Click the element matching the given XPath expression.
     */
    public function clickAtXPath(string $expression): static
    {
        $this->page->locator('xpath='.$expression)->first()->click($this->actionOptions());

        return $this;
    }

    /**
     * Click the element once it is enabled. Playwright's click natively waits
     * for exactly this actionability, so a plain click is the faithful mapping.
     */
    public function clickWhenEnabled(string $selector): static
    {
        return $this->click($selector);
    }

    /**
     * Click the element once it is visible. Playwright's click natively waits
     * for exactly this actionability, so a plain click is the faithful mapping.
     */
    public function clickWhenVisible(string $selector): static
    {
        return $this->click($selector);
    }

    /**
     * Press and hold the mouse button, optionally over the given selector.
     */
    public function clickAndHold(?string $selector = null): static
    {
        if ($selector !== null) {
            [$this->mouseX, $this->mouseY] = $this->elementCenter($selector);
            $this->page->mouse()->move($this->mouseX, $this->mouseY);
        }

        $this->page->mouse()->down();

        return $this;
    }

    /**
     * Release the mouse button.
     */
    public function releaseMouse(): static
    {
        $this->page->mouse()->up();

        return $this;
    }

    /**
     * Double click the element matching the given selector, or at the current
     * mouse position when no selector is given.
     */
    public function doubleClick(?string $selector = null): static
    {
        if ($selector === null) {
            $this->page->mouse()->dblclick($this->mouseX, $this->mouseY);

            return $this;
        }

        $this->resolver->resolve($selector)->dblclick($this->actionOptions());

        return $this;
    }

    /**
     * Right click the element matching the given selector, or at the current
     * mouse position when no selector is given.
     */
    public function rightClick(?string $selector = null): static
    {
        if ($selector === null) {
            $this->page->mouse()->click($this->mouseX, $this->mouseY, ['button' => 'right']);

            return $this;
        }

        $this->resolver->resolve($selector)->click($this->actionOptions(['button' => 'right']));

        return $this;
    }

    /**
     * Control-click (Windows/Linux) / Command-click (macOS) the element, or at
     * the current mouse position when no selector is given.
     */
    public function controlClick(?string $selector = null): static
    {
        if ($selector === null) {
            $keyboard = $this->page->keyboard();
            $keyboard->down('ControlOrMeta');

            try {
                $this->page->mouse()->click($this->mouseX, $this->mouseY);
            } finally {
                $keyboard->up('ControlOrMeta');
            }

            return $this;
        }

        $this->resolver->resolve($selector)->click($this->actionOptions(['modifiers' => ['ControlOrMeta']]));

        return $this;
    }

    /**
     * Scroll the element matching the given selector into view.
     */
    public function scrollIntoView(string $selector): static
    {
        $this->resolver->resolve($selector)->evaluate('el => el.scrollIntoView()');

        return $this;
    }

    /**
     * Scroll the page to the element matching the given selector.
     */
    public function scrollTo(string $selector): static
    {
        return $this->scrollIntoView($selector);
    }

    /**
     * The viewport-relative center of the first element matching the selector.
     *
     * @return array{0: float, 1: float}
     */
    protected function elementCenter(string $selector): array
    {
        $center = $this->resolver->resolve($selector)->evaluate(
            'el => { const r = el.getBoundingClientRect(); return [r.x + r.width / 2, r.y + r.height / 2]; }'
        );

        if (is_array($center) && isset($center[0], $center[1]) && is_numeric($center[0]) && is_numeric($center[1])) {
            return [(float) $center[0], (float) $center[1]];
        }

        return [$this->mouseX, $this->mouseY];
    }
}
