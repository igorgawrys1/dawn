<?php

declare(strict_types=1);

namespace Dawn\Concerns;

use Dawn\Exceptions\UnsupportedDuskMethod;

trait InteractsWithMouse
{
    /**
     * Move the mouse over the element matching the given selector.
     */
    public function mouseover(string $selector): static
    {
        $this->resolver->resolve($selector)->hover($this->actionOptions());

        return $this;
    }

    /**
     * Click the element matching the given selector.
     */
    public function click(?string $selector = null): static
    {
        if ($selector === null) {
            throw UnsupportedDuskMethod::make('click', 'clicking at the current mouse position is not supported yet - pass a selector');
        }

        $this->resolver->resolve($selector)->click($this->actionOptions());

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
     * Double click the element matching the given selector.
     */
    public function doubleClick(?string $selector = null): static
    {
        if ($selector === null) {
            throw UnsupportedDuskMethod::make('doubleClick', 'double clicking at the current mouse position is not supported yet - pass a selector');
        }

        $this->resolver->resolve($selector)->dblclick($this->actionOptions());

        return $this;
    }

    /**
     * Right click the element matching the given selector.
     */
    public function rightClick(?string $selector = null): static
    {
        if ($selector === null) {
            throw UnsupportedDuskMethod::make('rightClick', 'right clicking at the current mouse position is not supported yet - pass a selector');
        }

        $this->resolver->resolve($selector)->click($this->actionOptions(['button' => 'right']));

        return $this;
    }

    /**
     * Control-click (Windows/Linux) / Command-click (macOS) the element.
     */
    public function controlClick(?string $selector = null): static
    {
        if ($selector === null) {
            throw UnsupportedDuskMethod::make('controlClick', 'control clicking at the current mouse position is not supported yet - pass a selector');
        }

        $this->resolver->resolve($selector)->click($this->actionOptions(['modifiers' => ['ControlOrMeta']]));

        return $this;
    }

    public function moveMouse(int $xOffset, int $yOffset): static
    {
        throw UnsupportedDuskMethod::make('moveMouse');
    }

    public function clickAtPoint(int $x, int $y): static
    {
        throw UnsupportedDuskMethod::make('clickAtPoint');
    }

    public function clickAndHold(?string $selector = null): static
    {
        throw UnsupportedDuskMethod::make('clickAndHold');
    }

    public function releaseMouse(): static
    {
        throw UnsupportedDuskMethod::make('releaseMouse');
    }
}
