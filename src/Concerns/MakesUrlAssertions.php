<?php

declare(strict_types=1);

namespace Dawn\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\Constraint\RegularExpression;

trait MakesUrlAssertions
{
    /**
     * Assert that the current URL (without query) matches the given pattern
     * ("*" wildcards supported).
     */
    public function assertUrlIs(string $url): static
    {
        $pattern = str_replace('\*', '.*', preg_quote($url, '/'));

        $segments = parse_url($this->currentUrl());

        $currentUrl = sprintf(
            '%s://%s%s%s',
            $segments['scheme'] ?? '',
            $segments['host'] ?? '',
            isset($segments['port']) ? ':'.$segments['port'] : '',
            $segments['path'] ?? '',
        );

        PHPUnit::assertThat(
            $currentUrl,
            new RegularExpression('/^'.$pattern.'$/u'),
            "Actual URL [{$this->currentUrl()}] does not equal expected URL [{$url}]."
        );

        return $this;
    }

    /**
     * Assert that the current scheme matches the given pattern.
     */
    public function assertSchemeIs(string $scheme): static
    {
        $pattern = str_replace('\*', '.*', preg_quote($scheme, '/'));

        $actual = parse_url($this->currentUrl(), PHP_URL_SCHEME) ?? '';

        PHPUnit::assertThat(
            $actual,
            new RegularExpression('/^'.$pattern.'$/u'),
            "Actual scheme [{$actual}] does not equal expected scheme [{$pattern}]."
        );

        return $this;
    }

    /**
     * Assert that the current scheme does not equal the given scheme.
     */
    public function assertSchemeIsNot(string $scheme): static
    {
        $actual = parse_url($this->currentUrl(), PHP_URL_SCHEME) ?? '';

        PHPUnit::assertNotEquals(
            $scheme,
            $actual,
            "Scheme [{$scheme}] should not equal the actual value."
        );

        return $this;
    }

    /**
     * Assert that the current host matches the given pattern.
     */
    public function assertHostIs(string $host): static
    {
        $pattern = str_replace('\*', '.*', preg_quote($host, '/'));

        $actual = parse_url($this->currentUrl(), PHP_URL_HOST) ?? '';

        PHPUnit::assertThat(
            $actual,
            new RegularExpression('/^'.$pattern.'$/u'),
            "Actual host [{$actual}] does not equal expected host [{$pattern}]."
        );

        return $this;
    }

    /**
     * Assert that the current host does not equal the given host.
     */
    public function assertHostIsNot(string $host): static
    {
        $actual = parse_url($this->currentUrl(), PHP_URL_HOST) ?? '';

        PHPUnit::assertNotEquals(
            $host,
            $actual,
            "Host [{$host}] should not equal the actual value."
        );

        return $this;
    }

    /**
     * Assert that the current port matches the given pattern.
     */
    public function assertPortIs(string|int $port): static
    {
        $pattern = str_replace('\*', '.*', preg_quote((string) $port, '/'));

        $actual = (string) parse_url($this->currentUrl(), PHP_URL_PORT);

        PHPUnit::assertThat(
            $actual,
            new RegularExpression('/^'.$pattern.'$/u'),
            "Actual port [{$actual}] does not equal expected port [{$pattern}]."
        );

        return $this;
    }

    /**
     * Assert that the current port does not equal the given port.
     */
    public function assertPortIsNot(string|int $port): static
    {
        $actual = parse_url($this->currentUrl(), PHP_URL_PORT);

        PHPUnit::assertNotEquals(
            $port,
            $actual,
            "Port [{$port}] should not equal the actual value."
        );

        return $this;
    }

    /**
     * Assert that the current path begins with the given path.
     */
    public function assertPathBeginsWith(string $path): static
    {
        $actualPath = $this->currentUrlComponent(PHP_URL_PATH);

        PHPUnit::assertTrue(
            str_starts_with($actualPath, $path),
            "Actual path [{$actualPath}] does not begin with expected path [{$path}]."
        );

        return $this;
    }

    /**
     * Assert that the current path ends with the given path.
     */
    public function assertPathEndsWith(string $path): static
    {
        $actualPath = $this->currentUrlComponent(PHP_URL_PATH);

        PHPUnit::assertTrue(
            str_ends_with($actualPath, $path),
            "Actual path [{$actualPath}] does not end with expected path [{$path}]."
        );

        return $this;
    }

    /**
     * Assert that the current path contains the given string.
     */
    public function assertPathContains(string $path): static
    {
        $actualPath = $this->currentUrlComponent(PHP_URL_PATH);

        PHPUnit::assertTrue(
            str_contains($actualPath, $path),
            "Actual path [{$actualPath}] does not contain the expected string [{$path}]."
        );

        return $this;
    }

    /**
     * Assert that the current path matches the given pattern ("*" wildcards supported).
     */
    public function assertPathIs(string $path): static
    {
        $pattern = str_replace('\*', '.*', preg_quote($path, '/'));

        $actualPath = $this->currentUrlComponent(PHP_URL_PATH);

        PHPUnit::assertThat(
            $actualPath,
            new RegularExpression('/^'.$pattern.'$/u'),
            "Actual path [{$actualPath}] does not equal expected path [{$path}]."
        );

        return $this;
    }

    /**
     * Assert that the current path does not equal the given path.
     */
    public function assertPathIsNot(string $path): static
    {
        $actualPath = $this->currentUrlComponent(PHP_URL_PATH);

        PHPUnit::assertNotEquals(
            $path,
            $actualPath,
            "Path [{$path}] should not equal the actual value."
        );

        return $this;
    }

    /**
     * Assert that the current path matches the given named route.
     *
     * @param  array<array-key, mixed>  $parameters
     */
    public function assertRouteIs(string $route, array $parameters = []): static
    {
        return $this->assertPathIs(route($route, $parameters, false));
    }

    /**
     * Assert that the query string has the given parameter (and value).
     *
     * @param  string|array<int, string>|null  $value
     */
    public function assertQueryStringHas(string $name, string|array|null $value = null): static
    {
        $output = $this->assertHasQueryStringParameter($name);

        if ($value === null) {
            return $this;
        }

        $parsedOutputName = $this->stringifyQueryValue($output[$name]);

        $parsedValue = is_array($value) ? implode(',', $value) : $value;

        PHPUnit::assertEquals(
            $value,
            $output[$name],
            "Query string parameter [{$name}] had value [{$parsedOutputName}], but expected [{$parsedValue}]."
        );

        return $this;
    }

    /**
     * Assert that the query string is missing the given parameter.
     */
    public function assertQueryStringMissing(string $name): static
    {
        parse_str($this->currentUrlComponent(PHP_URL_QUERY), $output);

        PHPUnit::assertArrayNotHasKey(
            $name,
            $output,
            "Found unexpected query string parameter [{$name}] in [".$this->currentUrl().'].'
        );

        return $this;
    }

    /**
     * Assert that the current fragment matches the given pattern.
     */
    public function assertFragmentIs(string $fragment): static
    {
        $pattern = preg_quote($fragment, '/');

        $actualFragment = $this->currentUrlComponent(PHP_URL_FRAGMENT);

        PHPUnit::assertThat(
            $actualFragment,
            new RegularExpression('/^'.str_replace('\*', '.*', $pattern).'$/u'),
            "Actual fragment [{$actualFragment}] does not equal expected fragment [{$fragment}]."
        );

        return $this;
    }

    /**
     * Assert that the current fragment begins with the given fragment.
     */
    public function assertFragmentBeginsWith(string $fragment): static
    {
        $actualFragment = $this->currentUrlComponent(PHP_URL_FRAGMENT);

        PHPUnit::assertTrue(
            str_starts_with($actualFragment, $fragment),
            "Actual fragment [{$actualFragment}] does not begin with expected fragment [{$fragment}]."
        );

        return $this;
    }

    /**
     * Assert that the current fragment does not equal the given fragment.
     */
    public function assertFragmentIsNot(string $fragment): static
    {
        $actualFragment = $this->currentUrlComponent(PHP_URL_FRAGMENT);

        PHPUnit::assertNotEquals(
            $fragment,
            $actualFragment,
            "Fragment [{$fragment}] should not equal the actual value."
        );

        return $this;
    }

    /**
     * Assert that the query string exists and has the given parameter.
     *
     * @return array<array-key, mixed>
     */
    protected function assertHasQueryStringParameter(string $name): array
    {
        $query = parse_url($this->currentUrl(), PHP_URL_QUERY);

        PHPUnit::assertIsString(
            $query,
            'Did not see expected query string in ['.$this->currentUrl().'].'
        );

        parse_str($query, $output);

        PHPUnit::assertArrayHasKey(
            $name,
            $output,
            "Did not see expected query string parameter [{$name}] in [".$this->currentUrl().'].'
        );

        return $output;
    }

    /**
     * The browser's current URL, fragment included.
     */
    protected function currentUrl(): string
    {
        return $this->page->url();
    }

    /**
     * A component of the current URL (PHP_URL_PATH, PHP_URL_QUERY, ...),
     * normalised to a string.
     */
    protected function currentUrlComponent(int $component): string
    {
        $value = parse_url($this->currentUrl(), $component);

        return is_string($value) ? $value : '';
    }

    /**
     * Render a parse_str() output value the way Dusk displays it.
     */
    protected function stringifyQueryValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(',', array_map(
                static fn (mixed $item): string => is_scalar($item) ? (string) $item : '',
                $value,
            ));
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
