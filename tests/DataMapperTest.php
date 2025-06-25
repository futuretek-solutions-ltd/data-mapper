<?php

use futuretek\datamapper\DataMapper;
use futuretek\datamapper\tests\classes\TestFileFactory;
use futuretek\datamapper\tests\fixtures\AllTypesDto;

require_once __DIR__ . '/../vendor/autoload.php';

beforeEach(function () {
    DataMapper::$validateRequiredProperties = false;
    DataMapper::$fileFactory = new TestFileFactory();
});

test('maps all fields correctly from array to object and back', function () {
    $input = include __DIR__ . '/fixtures/all_types_array.php';
    $dto = DataMapper::toObject($input, AllTypesDto::class);
    $output = DataMapper::toArray($dto);

    expect($dto)->toBeInstanceOf(AllTypesDto::class);
    expect($output)->toMatchArray($input);
});

test('throws when required field is missing and validateRequiredProperties is true', function () {
    $input = include __DIR__ . '/fixtures/all_types_array.php';
    unset($input['string']);

    DataMapper::$validateRequiredProperties = true;

    throws(fn() => DataMapper::toObject($input, AllTypesDto::class), "Missing required property 'string'");
});

test('ignores missing required field when validateRequiredProperties is false', function () {
    $input = include __DIR__ . '/fixtures/all_types_array.php';
    unset($input['string']);

    DataMapper::$validateRequiredProperties = false;
    $dto = DataMapper::toObject($input, AllTypesDto::class);

    expect((new ReflectionProperty($dto, 'string'))->isInitialized($dto))->toBeFalse();
});

test('ignores unknown input property', function () {
    $input = include __DIR__ . '/fixtures/all_types_array.php';
    $input['extraProperty'] = 'extra';

    $dto = DataMapper::toObject($input, AllTypesDto::class);
    expect(property_exists($dto, 'extraProperty'))->toBeFalse();
});

test('throws if scalar property receives incompatible type', function () {
    $input = include __DIR__ . '/fixtures/all_types_array.php';
    $input['int'] = ['invalid' => 'array'];

    throws(fn() => DataMapper::toObject($input, AllTypesDto::class), 'Cannot assign array to property futuretek\datamapper\tests\fixtures\AllTypesDto::$int of type int');
});
