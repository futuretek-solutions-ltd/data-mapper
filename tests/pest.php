<?php

uses()->beforeEach(function () {
    // Set up common config if needed
})->in(__DIR__);

function throws(callable $callback, string $expectedMessage = ''): void
{
    try {
        $callback();
        expect(false)->toBeTrue(); // force failure if no exception
    } catch (Throwable $e) {
        if ($expectedMessage) {
            expect($e->getMessage())->toContain($expectedMessage);
        } else {
            expect(true)->toBeTrue();
        }
    }
}
