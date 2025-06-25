<?php

return [
    'string' => 'hello world',
    'int' => 42,
    'float' => 3.14,
    'bool' => true,

    'nullableString' => null,
    'nullableInt' => 123,
    'nullableFloat' => 9.81,
    'nullableBool' => false,

    'dateOnly' => '2023-01-01',
    'dateTime' => '2023-01-01T12:30:45+00:00',

    'plainArray' => ['a', 'b', 'c'],

    'intArray' => [1, 2, 3, 4, 5],

    'objectArray' => [
        ['label' => 'first', 'count' => 1],
        ['label' => 'second', 'count' => 2],
    ],

    'stringMap' => [
        'en' => 'hello',
        'fr' => 'bonjour',
    ],

    'objectMap' => [
        'a' => ['label' => 'alpha', 'count' => 10],
        'b' => ['label' => 'beta', 'count' => 20],
    ],

    'enum' => 'a',

    'nestedObject' => [
        'label' => 'parent',
        'count' => 999,
    ],

    'file' => new SplFileObject(__FILE__),
    'uploadedFile' => new \futuretek\datamapper\tests\fixtures\MockUploadedFile(),

    'mixedValue' => ['x' => 1, 'y' => 2],
    'iterableValue' => ['x', 'y', 'z'],
    'objectValue' => ['foo' => 'bar'],

    //'unionValue' => '42',
    'readonlyValue' => 'readonly',
    'unsetValue' => 'default fallback',
    'untagged' => 'anything',
];
