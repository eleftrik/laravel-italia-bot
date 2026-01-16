<?php

declare(strict_types=1);

describe('Architectural tests', function (): void {
    arch()
        ->preset()
        ->php();

    arch()
        ->preset()
        ->security();

    arch()
        ->expect('App')
        ->toUseStrictTypes()
        ->not->toUse(['ds', 'dsq', 'print_r', 'sleep']);
});
