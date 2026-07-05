<?php

declare(strict_types=1);

namespace Dawn\Concerns;

use Illuminate\Support\Str;

trait InteractsWithJavascript
{
    /**
     * Execute JavaScript within the browser. Mirrors Dusk's script(): accepts
     * a script or an array of scripts (statements, with WebDriver-style
     * "return" support) and returns the array of results.
     *
     * @param  string|list<string>  $scripts
     * @return array<int, mixed>
     */
    public function script(string|array $scripts): array
    {
        return array_map(
            fn (string $script): mixed => $this->page->evaluate(
                '() => { '.Str::finish(trim($script), ';').' }'
            ),
            is_array($scripts) ? array_values($scripts) : [$scripts],
        );
    }
}
