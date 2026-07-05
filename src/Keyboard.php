<?php

declare(strict_types=1);

namespace Dawn;

use InvalidArgumentException;
use Playwright\Page\PageInterface;

/**
 * Translates Dusk's WebDriverKeys-style key tokens ("{enter}", "{command}",
 * modifier chords passed as arrays) into Playwright keyboard calls.
 */
final class Keyboard
{
    /**
     * Dusk "{token}" names mapped to Playwright key names.
     *
     * @var array<string, string>
     */
    private const KEY_MAP = [
        'backspace' => 'Backspace',
        'tab' => 'Tab',
        'enter' => 'Enter',
        'return' => 'Enter',
        'shift' => 'Shift',
        'left_shift' => 'Shift',
        'control' => 'Control',
        'left_control' => 'Control',
        'alt' => 'Alt',
        'left_alt' => 'Alt',
        'pause' => 'Pause',
        'escape' => 'Escape',
        'space' => 'Space',
        'page_up' => 'PageUp',
        'page_down' => 'PageDown',
        'end' => 'End',
        'home' => 'Home',
        'left' => 'ArrowLeft',
        'arrow_left' => 'ArrowLeft',
        'up' => 'ArrowUp',
        'arrow_up' => 'ArrowUp',
        'right' => 'ArrowRight',
        'arrow_right' => 'ArrowRight',
        'down' => 'ArrowDown',
        'arrow_down' => 'ArrowDown',
        'insert' => 'Insert',
        'delete' => 'Delete',
        'semicolon' => ';',
        'equals' => '=',
        'numpad0' => 'Numpad0',
        'numpad1' => 'Numpad1',
        'numpad2' => 'Numpad2',
        'numpad3' => 'Numpad3',
        'numpad4' => 'Numpad4',
        'numpad5' => 'Numpad5',
        'numpad6' => 'Numpad6',
        'numpad7' => 'Numpad7',
        'numpad8' => 'Numpad8',
        'numpad9' => 'Numpad9',
        'multiply' => 'NumpadMultiply',
        'add' => 'NumpadAdd',
        'subtract' => 'NumpadSubtract',
        'decimal' => 'NumpadDecimal',
        'divide' => 'NumpadDivide',
        'f1' => 'F1',
        'f2' => 'F2',
        'f3' => 'F3',
        'f4' => 'F4',
        'f5' => 'F5',
        'f6' => 'F6',
        'f7' => 'F7',
        'f8' => 'F8',
        'f9' => 'F9',
        'f10' => 'F10',
        'f11' => 'F11',
        'f12' => 'F12',
        'meta' => 'Meta',
        'command' => 'Meta',
    ];

    private const MODIFIERS = ['Shift', 'Control', 'Alt', 'Meta'];

    /**
     * Send a mixed list of Dusk key inputs to the focused element.
     *
     * @param  list<string|list<string>>  $keys
     */
    public static function send(PageInterface $page, array $keys): void
    {
        $keyboard = $page->keyboard();

        foreach ($keys as $key) {
            if (is_array($key)) {
                self::sendChord($page, $key);

                continue;
            }

            if (self::isToken($key)) {
                $keyboard->press(self::translate($key));

                continue;
            }

            $keyboard->type($key);
        }
    }

    /**
     * Send a Dusk modifier chord (e.g. ['{shift}', 'taylor']): every
     * non-modifier key is pressed with the chord's modifiers applied.
     * Playwright's type() ignores held modifiers, so press("Shift+x") is the
     * correct translation.
     *
     * @param  list<string>  $chord
     */
    private static function sendChord(PageInterface $page, array $chord): void
    {
        $keyboard = $page->keyboard();

        $modifiers = [];

        foreach ($chord as $key) {
            if (self::isToken($key)) {
                $translated = self::translate($key);

                if (in_array($translated, self::MODIFIERS, true)) {
                    $modifiers[] = $translated;

                    continue;
                }

                $keyboard->press(self::withModifiers($modifiers, $translated));

                continue;
            }

            foreach (mb_str_split($key) as $character) {
                $keyboard->press(self::withModifiers($modifiers, $character));
            }
        }
    }

    /**
     * @param  list<string>  $modifiers
     */
    private static function withModifiers(array $modifiers, string $key): string
    {
        if ($modifiers === []) {
            return $key;
        }

        // Playwright's press() types a single character verbatim, so a Dusk
        // shift-chord must uppercase the character itself.
        if (in_array('Shift', $modifiers, true) && mb_strlen($key) === 1) {
            $key = mb_strtoupper($key);
        }

        return implode('+', [...$modifiers, $key]);
    }

    /**
     * Translate a "{token}" into a Playwright key name.
     */
    public static function translate(string $token): string
    {
        $name = strtolower(trim($token, '{}'));

        if (! isset(self::KEY_MAP[$name])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown key token [%s]. Supported tokens: %s.',
                $token,
                implode(', ', array_keys(self::KEY_MAP)),
            ));
        }

        return self::KEY_MAP[$name];
    }

    private static function isToken(string $key): bool
    {
        return strlen($key) > 2 && str_starts_with($key, '{') && str_ends_with($key, '}');
    }
}
