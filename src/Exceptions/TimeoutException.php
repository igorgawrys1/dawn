<?php

declare(strict_types=1);

namespace Dawn\Exceptions;

use RuntimeException;

/**
 * Thrown when a waitFor* condition is not met within the allotted time.
 *
 * Mirrors the role of the WebDriver TimeoutException thrown by Dusk's waits,
 * without depending on any WebDriver types.
 */
final class TimeoutException extends RuntimeException {}
