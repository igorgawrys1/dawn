<?php

declare(strict_types=1);

namespace Dawn;

use Closure;
use Dawn\Exceptions\UnsupportedDuskMethod;
use Illuminate\Support\Traits\Macroable;
use Playwright\Console\ConsoleMessage;
use Playwright\Dialog\DialogInterface;
use Playwright\Page\PageInterface;

/**
 * @phpstan-consistent-constructor
 */
class Browser
{
    use Concerns\InteractsWithAuthentication;
    use Concerns\InteractsWithCookies;
    use Concerns\InteractsWithElements;
    use Concerns\InteractsWithJavascript;
    use Concerns\InteractsWithMouse;
    use Concerns\MakesAssertions;
    use Concerns\MakesUrlAssertions;
    use Concerns\WaitsForElements;
    use Macroable {
        __call as macroCall;
    }

    /**
     * The base URL for all relative URLs.
     */
    public static ?string $baseUrl = null;

    /**
     * The directory that will contain any screenshots.
     */
    public static string $storeScreenshotsAt = '';

    /**
     * The directory that will contain any console logs.
     */
    public static string $storeConsoleLogAt = '';

    /**
     * The directory that will contain any captured page source.
     */
    public static string $storeSourceAt = '';

    /**
     * The default wait time in seconds for waitFor* methods.
     */
    public static int $waitSeconds = 5;

    /**
     * The callback that resolves the default user for login(). Set by
     * Dawn\TestCase, overridable per suite exactly like Dusk's.
     *
     * @var (Closure(): \Illuminate\Contracts\Auth\Authenticatable)|null
     */
    public static ?Closure $userResolver = null;

    /**
     * Console messages captured for this browser instance.
     *
     * @var list<array{type: string, text: string, location: array<string, mixed>}>
     */
    public array $consoleMessages = [];

    /**
     * Dialogs captured by the always-on listener. Driving them from the test
     * body is not supported (see COMPATIBILITY.md); this buffer exists only so
     * failure capture can dismiss a dialog a failing test left open, keeping
     * artifacts and later tests from being blocked.
     *
     * @var list<DialogInterface>
     */
    public array $pendingDialogs = [];

    /**
     * The page object the browser is currently on, if any.
     */
    public ?Page $currentPage = null;

    /**
     * The component the browser is currently scoped to, if any.
     */
    public ?Component $currentComponent = null;

    public ElementResolver $resolver;

    public function __construct(
        public PageInterface $page,
        ?ElementResolver $resolver = null,
    ) {
        $this->resolver = $resolver ?? new ElementResolver($page);

        if ($resolver === null) {
            $this->page->events()->onConsole(function (ConsoleMessage $message): void {
                $this->consoleMessages[] = [
                    'type' => $message->type(),
                    'text' => $message->text(),
                    'location' => $message->location(),
                ];
            });

            $this->page->events()->onDialog(function (DialogInterface $dialog): void {
                $this->pendingDialogs[] = $dialog;
            });
        }
    }

    /**
     * Dismiss every captured-but-unhandled dialog. Used during failure
     * capture so a dialog left open by a failing test cannot block artifacts
     * (or contaminate later tests sharing the browser). Driving dialogs from
     * the test body is not supported - see COMPATIBILITY.md.
     */
    public function dismissPendingDialogs(): void
    {
        foreach ($this->pendingDialogs as $dialog) {
            try {
                $dialog->dismiss();
            } catch (\Throwable) {
                // The dialog may already be gone.
            }
        }

        $this->pendingDialogs = [];
    }

    /**
     * Browse to the given URL or Page object.
     */
    public function visit(string|Page $url): static
    {
        $page = null;

        if ($url instanceof Page) {
            $page = $url;
            $url = $page->url();
        }

        if (! str_contains($url, '://')) {
            $url = static::$baseUrl.'/'.ltrim($url, '/');
        }

        $this->page->goto($url);

        if ($page !== null) {
            $this->on($page);
        }

        return $this;
    }

    /**
     * Browse to the given named route.
     *
     * @param  array<array-key, mixed>  $parameters
     */
    public function visitRoute(string $route, array $parameters = []): static
    {
        return $this->visit(route($route, $parameters));
    }

    /**
     * Browse to the "about:blank" page.
     */
    public function blank(): static
    {
        $this->page->goto('about:blank');

        return $this;
    }

    /**
     * Refresh the current page.
     */
    public function refresh(): static
    {
        $this->page->reload();

        return $this;
    }

    /**
     * Navigate to the previous page.
     */
    public function back(): static
    {
        $this->page->goBack();

        return $this;
    }

    /**
     * Navigate to the next page.
     */
    public function forward(): static
    {
        $this->page->goForward();

        return $this;
    }

    /**
     * Resize the browser viewport.
     */
    public function resize(int $width, int $height): static
    {
        $this->page->setViewportSize($width, $height);

        return $this;
    }

    /**
     * Take a screenshot and store it with the given name.
     */
    public function screenshot(string $name): static
    {
        $filePath = sprintf('%s/%s.png', rtrim(static::$storeScreenshotsAt, '/'), $name);

        $this->ensureDirectoryExists($filePath);

        $this->page->screenshot($filePath);

        return $this;
    }

    /**
     * Take a series of screenshots at different viewport sizes.
     */
    public function responsiveScreenshots(string $name): static
    {
        // Match Dusk: a trailing "/" keeps the breakpoint as the file name in a
        // sub-directory; otherwise the breakpoint is appended with a hyphen.
        if (! str_ends_with($name, '/')) {
            $name .= '-';
        }

        $breakpoints = [
            'xs' => [360, 640],
            'sm' => [640, 360],
            'md' => [768, 1024],
            'lg' => [1024, 768],
            'xl' => [1280, 1024],
            '2xl' => [1536, 864],
        ];

        foreach ($breakpoints as $label => [$width, $height]) {
            $this->resize($width, $height)->screenshot($name.$label);
        }

        return $this;
    }

    /**
     * Take a screenshot of the element matching the given selector.
     */
    public function screenshotElement(string $selector, string $name): static
    {
        $filePath = sprintf('%s/%s.png', rtrim(static::$storeScreenshotsAt, '/'), $name);

        $this->ensureDirectoryExists($filePath);

        $this->resolver->resolve($selector)->screenshot($filePath);

        return $this;
    }

    /**
     * Store the captured console output with the given name.
     */
    public function storeConsoleLog(string $name): static
    {
        if ($this->consoleMessages !== []) {
            $filePath = sprintf('%s/%s.log', rtrim(static::$storeConsoleLogAt, '/'), $name);

            $this->ensureDirectoryExists($filePath);

            file_put_contents($filePath, json_encode($this->consoleMessages, JSON_PRETTY_PRINT));
        }

        return $this;
    }

    /**
     * Store the page source with the given name.
     */
    public function storeSource(string $name): static
    {
        $source = (string) $this->page->content();

        if ($source !== '') {
            $filePath = sprintf('%s/%s.txt', rtrim(static::$storeSourceAt, '/'), $name);

            $this->ensureDirectoryExists($filePath);

            file_put_contents($filePath, $source);
        }

        return $this;
    }

    /**
     * Execute the given callback within a browser scoped to the selector or component.
     */
    public function within(string|Component $selector, Closure $callback): static
    {
        return $this->with($selector, $callback);
    }

    /**
     * Execute the given callback within a browser scoped to the selector or component.
     */
    public function with(string|Component $selector, Closure $callback): static
    {
        // A Component's prefix is applied once by onComponent(); here it must
        // NOT be prepended, so it scopes from the current prefix (mirrors
        // Dusk, where Component::__toString() is empty for exactly this reason).
        $scope = $selector instanceof Component ? '' : $selector;

        $browser = new static(
            $this->page,
            new ElementResolver($this->page, $this->resolver->format($scope)),
        );

        $browser->consoleMessages = &$this->consoleMessages;
        $browser->pendingDialogs = &$this->pendingDialogs;

        if ($this->currentPage !== null) {
            $browser->onWithoutAssert($this->currentPage);
        }

        if ($selector instanceof Component) {
            $browser->onComponent($selector, $this->resolver);
        }

        $callback($browser);

        return $this;
    }

    /**
     * Execute the given callback in a browser scoped outside the current scope.
     */
    public function elsewhere(string $selector, Closure $callback): static
    {
        $browser = new static(
            $this->page,
            new ElementResolver($this->page, trim('body '.$selector)),
        );

        $browser->consoleMessages = &$this->consoleMessages;
        $browser->pendingDialogs = &$this->pendingDialogs;

        $callback($browser);

        return $this;
    }

    /**
     * Execute the given callback outside the current scope once the selector is available.
     */
    public function elsewhereWhenAvailable(string $selector, Closure $callback, ?int $seconds = null): static
    {
        return $this->elsewhere('', function (Browser $browser) use ($selector, $callback, $seconds): void {
            $browser->whenAvailable($selector, $callback, $seconds);
        });
    }

    /**
     * jQuery is never needed by Dawn; kept as a no-op for Dusk compatibility.
     */
    public function ensurejQueryIsAvailable(): static
    {
        return $this;
    }

    /**
     * Pause for the given number of milliseconds.
     *
     * The pause happens inside the browser's event loop (a resolved
     * setTimeout promise) - no PHP-side sleeping is involved.
     */
    public function pause(int $milliseconds): static
    {
        $this->page->evaluate('ms => new Promise(resolve => setTimeout(resolve, ms))', $milliseconds);

        return $this;
    }

    /**
     * Pause for the given number of milliseconds if the condition is true.
     */
    public function pauseIf(bool $boolean, int $milliseconds): static
    {
        return $boolean ? $this->pause($milliseconds) : $this;
    }

    /**
     * Pause for the given number of milliseconds unless the condition is true.
     */
    public function pauseUnless(bool $boolean, int $milliseconds): static
    {
        return $boolean ? $this : $this->pause($milliseconds);
    }

    /**
     * Close the browser context.
     */
    public function quit(): void
    {
        $this->page->context()->close();
    }

    /**
     * Tap the browser into the given callback.
     */
    public function tap(callable $callback): static
    {
        $callback($this);

        return $this;
    }

    /**
     * Dump the page source.
     */
    public function dump(): static
    {
        dump((string) $this->page->content());

        return $this;
    }

    /**
     * Dump the page source and end the script.
     */
    public function dd(): never
    {
        dd((string) $this->page->content());
    }

    public function maximize(): static
    {
        throw UnsupportedDuskMethod::make('maximize', 'Playwright uses viewports, not OS windows - use resize() instead');
    }

    /**
     * Resize the viewport to fit the document's rendered content.
     */
    public function fitContent(): static
    {
        $size = $this->page->evaluate(
            '() => { const el = document.documentElement; return [el.scrollWidth, el.scrollHeight]; }'
        );

        if (is_array($size) && isset($size[0], $size[1]) && is_numeric($size[0]) && is_numeric($size[1])
            && (int) $size[0] > 0 && (int) $size[1] > 0) {
            $this->resize((int) $size[0], (int) $size[1]);
        }

        return $this;
    }

    public function disableFitOnFailure(): static
    {
        return $this;
    }

    public function enableFitOnFailure(): static
    {
        return $this;
    }

    public function move(int $x, int $y): static
    {
        throw UnsupportedDuskMethod::make('move', 'Playwright uses viewports, not OS windows');
    }

    public function withinFrame(string $selector, Closure $callback): static
    {
        throw UnsupportedDuskMethod::make('withinFrame');
    }

    /**
     * Set the current page and assert that the browser is on it.
     */
    public function on(Page $page): static
    {
        $this->onWithoutAssert($page);

        $page->assert($this);

        return $this;
    }

    /**
     * Set the current page without asserting, registering its element shortcuts.
     */
    public function onWithoutAssert(Page $page): static
    {
        $this->currentPage = $page;

        $this->resolver->pageElements(array_merge(
            $page::siteElements(),
            $page->elements(),
        ));

        return $this;
    }

    /**
     * Create a browser scoped to the given component and assert its presence.
     */
    public function component(Component $component): static
    {
        // Start from the current prefix; onComponent() appends the component
        // selector exactly once.
        $browser = new static(
            $this->page,
            new ElementResolver($this->page, $this->resolver->format('')),
        );

        $browser->consoleMessages = &$this->consoleMessages;
        $browser->pendingDialogs = &$this->pendingDialogs;

        if ($this->currentPage !== null) {
            $browser->onWithoutAssert($this->currentPage);
        }

        $browser->onComponent($component, $this->resolver);

        return $browser;
    }

    /**
     * Bind the given component to this browser and register its shortcuts.
     */
    public function onComponent(Component $component, ElementResolver $parentResolver): static
    {
        $this->currentComponent = $component;

        $this->resolver->pageElements(
            $component->elements() + $parentResolver->elements
        );

        $component->assert($this);

        $this->resolver->prefix = $this->resolver->format($component->selector());

        return $this;
    }

    /**
     * Execute the given callback with a fluent keyboard instance.
     *
     * @param  callable(KeyboardActions): void  $callback
     */
    public function withKeyboard(callable $callback): static
    {
        $callback(new KeyboardActions($this->page));

        return $this;
    }

    public function tinker(): static
    {
        throw UnsupportedDuskMethod::make('tinker');
    }

    public function stop(): static
    {
        throw UnsupportedDuskMethod::make('stop');
    }

    /**
     * Options applied to every Playwright locator action. Playwright's
     * built-in default is 30 seconds and the engine's Node server does not
     * support page-level defaults, so Dawn passes Dusk's wait time explicitly
     * - genuine failures surface in seconds while native auto-waiting stays
     * active for every action.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function actionOptions(array $extra = []): array
    {
        return $extra + ['timeout' => static::$waitSeconds * 1000];
    }

    /**
     * The rendered text of the first element matching the given CSS selector,
     * read point-in-time exactly as Dusk's getText() - no waiting.
     *
     * @throws Exceptions\ElementNotFound
     */
    public function elementInnerText(string $formattedSelector): string
    {
        $target = json_encode($formattedSelector);

        $text = $this->page->evaluate(
            "() => { const el = document.querySelector({$target}); return el === null ? null : el.innerText; }"
        );

        if (! is_string($text)) {
            throw Exceptions\ElementNotFound::forSelector($formattedSelector);
        }

        return $text;
    }

    /**
     * Any unknown method: try registered macros, otherwise fail loudly with a
     * typed exception - never silently approximate a Dusk behaviour.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        throw UnsupportedDuskMethod::make($method);
    }

    protected function ensureDirectoryExists(string $filePath): void
    {
        $directoryPath = dirname($filePath);

        if (! is_dir($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }
    }
}
