<?php

declare(strict_types=1);

namespace Dawn\Support;

final class NativeSleeper implements Sleeper
{
    public function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
