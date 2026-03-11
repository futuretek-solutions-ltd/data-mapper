<?php

use futuretek\datamapper\DataMapper;
use futuretek\datamapper\tests\classes\TestFile;
use futuretek\datamapper\tests\classes\TestFileFactory;
use futuretek\datamapper\tests\fixtures\dtos\AllTypesDto;
use futuretek\datamapper\tests\fixtures\dtos\ArrayDto;
use futuretek\datamapper\tests\fixtures\dtos\BooleanEdgeCaseDto;
use futuretek\datamapper\tests\fixtures\dtos\DateDto;
use futuretek\datamapper\tests\fixtures\dtos\DateNoFormatDto;
use futuretek\datamapper\tests\fixtures\dtos\DeepNestedDto;
use futuretek\datamapper\tests\fixtures\dtos\EmptyDto;
use futuretek\datamapper\tests\fixtures\dtos\EnumDto;
use futuretek\datamapper\tests\fixtures\dtos\FileDto;
use futuretek\datamapper\tests\fixtures\dtos\FloatEdgeCaseDto;
use futuretek\datamapper\tests\fixtures\dtos\MapDto;
use futuretek\datamapper\tests\fixtures\dtos\MixedTypesDto;
use futuretek\datamapper\tests\fixtures\dtos\MultiReadonlyDto;
use futuretek\datamapper\tests\fixtures\dtos\NestedDto;
use futuretek\datamapper\tests\fixtures\dtos\NullableArrayDto;
use futuretek\datamapper\tests\fixtures\dtos\NullableDateDto;
use futuretek\datamapper\tests\fixtures\dtos\ReadonlyDto;
use futuretek\datamapper\tests\fixtures\dtos\ScalarDto;
use futuretek\datamapper\tests\fixtures\dtos\StaticDto;
use futuretek\datamapper\tests\fixtures\dtos\TestEnum;
use futuretek\datamapper\tests\fixtures\dtos\TestObject;
use futuretek\datamapper\tests\fixtures\MockUploadedFile;

require_once __DIR__ . '/../vendor/autoload.php';

beforeEach(function () {
    DataMapper::$validateRequiredProperties = false;
    DataMapper::$fileFactory = new TestFileFactory();
});

// ============================================================
// Integration / Smoke Test
// ============================================================

test('maps all fields correctly from array to object and back', function () {
    $input = include __DIR__ . '/fixtures/all_types_array.php';
    $dto = DataMapper::toObject($input, AllTypesDto::class);
    $output = DataMapper::toArray($dto);

    expect($dto)->toBeInstanceOf(AllTypesDto::class);
    expect($output)->toMatchArray($input);
});

// ============================================================
// Scalar Types
// ============================================================

test('maps scalar properties correctly', function () {
    $dto = DataMapper::toObject(['name' => 'Alice', 'age' => 30], ScalarDto::class);

    expect($dto->name)->toBe('Alice');
    expect($dto->age)->toBe(30);
});

test('maps nullable scalar with value', function () {
    $dto = DataMapper::toObject([
        'name' => 'Bob',
        'age' => 25,
        'nickname' => 'Bobby',
        'score' => 100,
    ], ScalarDto::class);

    expect($dto->nickname)->toBe('Bobby');
    expect($dto->score)->toBe(100);
});

test('maps nullable scalar with null value', function () {
    $dto = DataMapper::toObject([
        'name' => 'Charlie',
        'age' => 20,
        'nickname' => null,
        'score' => null,
    ], ScalarDto::class);

    expect($dto->nickname)->toBeNull();
    expect($dto->score)->toBeNull();
});

test('scalar round-trip preserves values', function () {
    $input = ['name' => 'Dana', 'age' => 42, 'nickname' => 'D', 'score' => 99];
    $dto = DataMapper::toObject($input, ScalarDto::class);
    $output = DataMapper::toArray($dto);

    expect($output)->toMatchArray($input);
});

// ============================================================
// Required / Missing Properties
// ============================================================

test('throws when required field is missing and validateRequiredProperties is true', function () {
    DataMapper::$validateRequiredProperties = true;

    expect(fn() => DataMapper::toObject(['age' => 10], ScalarDto::class))
        ->toThrow(InvalidArgumentException::class, "Missing required property 'name'");
});

test('ignores missing required field when validateRequiredProperties is false', function () {
    $dto = DataMapper::toObject(['age' => 10], ScalarDto::class);

    expect((new ReflectionProperty($dto, 'name'))->isInitialized($dto))->toBeFalse();
    expect($dto->age)->toBe(10);
});

test('creates object from empty array with validation off', function () {
    $dto = DataMapper::toObject([], EmptyDto::class);

    expect((new ReflectionProperty($dto, 'required'))->isInitialized($dto))->toBeFalse();
    expect($dto->optional)->toBeNull();
});

test('missing nullable property retains default value', function () {
    $dto = DataMapper::toObject(['name' => 'Eve', 'age' => 22], ScalarDto::class);

    expect($dto->nickname)->toBeNull();
    expect($dto->score)->toBeNull();
});

// ============================================================
// Unknown / Extra Input
// ============================================================

test('ignores unknown input properties', function () {
    $dto = DataMapper::toObject([
        'name' => 'Frank',
        'age' => 33,
        'unknownField' => 'ignored',
    ], ScalarDto::class);

    expect($dto->name)->toBe('Frank');
    expect(property_exists($dto, 'unknownField'))->toBeFalse();
});

// ============================================================
// Type Errors
// ============================================================

test('throws when scalar property receives incompatible type', function () {
    expect(fn() => DataMapper::toObject([
        'name' => 'Grace',
        'age' => ['invalid' => 'array'],
    ], ScalarDto::class))
        ->toThrow(Error::class);
});

// ============================================================
// DateTime Format
// ============================================================

test('maps date and datetime properties correctly', function () {
    $dto = DataMapper::toObject([
        'dateOnly' => '2023-06-15',
        'dateTime' => '2023-06-15T10:30:00+00:00',
    ], DateDto::class);

    expect($dto->dateOnly)->toBeInstanceOf(DateTimeImmutable::class);
    expect($dto->dateOnly->format('Y-m-d'))->toBe('2023-06-15');
    expect($dto->dateTime)->toBeInstanceOf(DateTimeImmutable::class);
    expect($dto->dateTime->format('Y-m-d\TH:i:sP'))->toBe('2023-06-15T10:30:00+00:00');
});

test('malformed date string sets property to null', function () {
    $dto = DataMapper::toObject([
        'dateOnly' => 'not-a-date',
        'dateTime' => 'also-not-valid',
    ], NullableDateDto::class);

    expect($dto->dateOnly)->toBeNull();
    expect($dto->dateTime)->toBeNull();
});

test('date round-trip preserves values', function () {
    $input = [
        'dateOnly' => '2024-12-25',
        'dateTime' => '2024-12-25T08:00:00+00:00',
    ];
    $dto = DataMapper::toObject($input, DateDto::class);
    $output = DataMapper::toArray($dto);

    expect($output['dateOnly'])->toBe('2024-12-25');
    expect($output['dateTime'])->toBe('2024-12-25T08:00:00+00:00');
});

test('toArray defaults to date-time format when no Format attribute', function () {
    $dto = new DateNoFormatDto();
    $dto->createdAt = new DateTimeImmutable('2024-01-01T12:00:00+00:00');

    $output = DataMapper::toArray($dto);

    expect($output['createdAt'])->toBe('2024-01-01T12:00:00+00:00');
});

test('toObject parses DateTimeInterface property without Format attribute', function () {
    $dto = DataMapper::toObject([
        'createdAt' => '2024-03-15T14:30:00+00:00',
    ], DateNoFormatDto::class);

    expect($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
    expect($dto->createdAt->format('Y-m-d'))->toBe('2024-03-15');
});

test('toObject accepts various datetime string formats', function (string $input, string $expectedDate) {
    $dto = DataMapper::toObject(['createdAt' => $input], DateNoFormatDto::class);

    expect($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
    expect($dto->createdAt->format('Y-m-d'))->toBe($expectedDate);
})->with([
    'ISO 8601 with timezone' => ['2024-06-15T10:30:00+02:00', '2024-06-15'],
    'ISO 8601 UTC (Z)' => ['2024-06-15T10:30:00Z', '2024-06-15'],
    'date only' => ['2024-06-15', '2024-06-15'],
    'datetime without timezone' => ['2024-06-15 10:30:00', '2024-06-15'],
    'US style' => ['06/15/2024', '2024-06-15'],
    'relative today' => ['today', (new DateTimeImmutable('today'))->format('Y-m-d')],
    'relative tomorrow' => ['tomorrow', (new DateTimeImmutable('tomorrow'))->format('Y-m-d')],
    'relative yesterday' => ['yesterday', (new DateTimeImmutable('yesterday'))->format('Y-m-d')],
    'textual month' => ['15 June 2024', '2024-06-15'],
    'textual month short' => ['15 Jun 2024', '2024-06-15'],
    'RFC 2822' => ['Sat, 15 Jun 2024 10:30:00 +0000', '2024-06-15'],
    'year-month only' => ['2024-06', '2024-06-01'],
    'datetime with microseconds' => ['2024-06-15T10:30:00.123456+00:00', '2024-06-15'],
]);

test('toObject accepts various datetime formats with Format attribute', function (string $input, string $expectedDate) {
    $dto = DataMapper::toObject([
        'dateOnly' => $input,
        'dateTime' => $input,
    ], DateDto::class);

    expect($dto->dateOnly)->toBeInstanceOf(DateTimeImmutable::class);
    expect($dto->dateOnly->format('Y-m-d'))->toBe($expectedDate);
    expect($dto->dateTime)->toBeInstanceOf(DateTimeImmutable::class);
    expect($dto->dateTime->format('Y-m-d'))->toBe($expectedDate);
})->with([
    'ISO 8601' => ['2024-06-15T10:30:00+00:00', '2024-06-15'],
    'date only' => ['2024-06-15', '2024-06-15'],
    'datetime space-separated' => ['2024-06-15 10:30:00', '2024-06-15'],
    'textual month' => ['15 June 2024', '2024-06-15'],
]);

test('toObject sets null for completely invalid datetime string', function () {
    $dto = DataMapper::toObject([
        'dateOnly' => 'not-a-date-at-all',
        'dateTime' => '!!!invalid!!!',
    ], NullableDateDto::class);

    expect($dto->dateOnly)->toBeNull();
    expect($dto->dateTime)->toBeNull();
});

// ============================================================
// Enums
// ============================================================

test('maps valid enum value', function () {
    $dto = DataMapper::toObject(['status' => 'a', 'optionalStatus' => 'b'], EnumDto::class);

    expect($dto->status)->toBe(TestEnum::A);
    expect($dto->optionalStatus)->toBe(TestEnum::B);
});

test('throws on invalid enum value', function () {
    expect(fn() => DataMapper::toObject(['status' => 'z', 'optionalStatus' => null], EnumDto::class))
        ->toThrow(UnexpectedValueException::class, "Invalid enum value 'z'");
});

test('enum round-trip preserves value', function () {
    $input = ['status' => 'b', 'optionalStatus' => 'a'];
    $dto = DataMapper::toObject($input, EnumDto::class);
    $output = DataMapper::toArray($dto);

    expect($output['status'])->toBe('b');
    expect($output['optionalStatus'])->toBe('a');
});

// ============================================================
// Nested Objects
// ============================================================

test('maps nested object from array', function () {
    $dto = DataMapper::toObject([
        'title' => 'Test',
        'child' => ['label' => 'inner', 'count' => 5],
    ], NestedDto::class);

    expect($dto->child)->toBeInstanceOf(TestObject::class);
    expect($dto->child->label)->toBe('inner');
    expect($dto->child->count)->toBe(5);
});

test('maps optional nested object as null', function () {
    $dto = DataMapper::toObject([
        'title' => 'Test',
        'child' => ['label' => 'inner', 'count' => 1],
        'optionalChild' => null,
    ], NestedDto::class);

    expect($dto->optionalChild)->toBeNull();
});

test('nested object round-trip preserves values', function () {
    $input = [
        'title' => 'Parent',
        'child' => ['label' => 'child', 'count' => 3],
    ];
    $dto = DataMapper::toObject($input, NestedDto::class);
    $output = DataMapper::toArray($dto);

    expect($output['title'])->toBe('Parent');
    expect($output['child'])->toBe(['label' => 'child', 'count' => 3]);
});

// ============================================================
// ArrayType
// ============================================================

test('maps plain array', function () {
    $dto = DataMapper::toObject([
        'plain' => ['a', 'b'],
        'ints' => [1, 2],
        'objects' => [],
    ], ArrayDto::class);

    expect($dto->plain)->toBe(['a', 'b']);
});

test('maps array of scalars with ArrayType', function () {
    $dto = DataMapper::toObject([
        'plain' => [],
        'ints' => [10, 20, 30],
        'objects' => [],
    ], ArrayDto::class);

    expect($dto->ints)->toBe([10, 20, 30]);
});

test('maps array of objects with ArrayType', function () {
    $dto = DataMapper::toObject([
        'plain' => [],
        'ints' => [],
        'objects' => [
            ['label' => 'x', 'count' => 1],
            ['label' => 'y', 'count' => 2],
        ],
    ], ArrayDto::class);

    expect($dto->objects)->toHaveCount(2);
    expect($dto->objects[0])->toBeInstanceOf(TestObject::class);
    expect($dto->objects[0]->label)->toBe('x');
    expect($dto->objects[1]->count)->toBe(2);
});

test('empty array with ArrayType maps to empty array', function () {
    $dto = DataMapper::toObject([
        'plain' => [],
        'ints' => [],
        'objects' => [],
    ], ArrayDto::class);

    expect($dto->objects)->toBe([]);
});

test('ArrayType object round-trip preserves values', function () {
    $input = [
        'plain' => ['foo'],
        'ints' => [1, 2, 3],
        'objects' => [
            ['label' => 'a', 'count' => 10],
        ],
    ];
    $dto = DataMapper::toObject($input, ArrayDto::class);
    $output = DataMapper::toArray($dto);

    expect($output)->toMatchArray($input);
});

// ============================================================
// MapType
// ============================================================

test('maps string map correctly', function () {
    $dto = DataMapper::toObject([
        'stringMap' => ['en' => 'hello', 'fr' => 'bonjour'],
        'objectMap' => [],
    ], MapDto::class);

    expect($dto->stringMap)->toBe(['en' => 'hello', 'fr' => 'bonjour']);
});

test('maps object map correctly', function () {
    $dto = DataMapper::toObject([
        'stringMap' => [],
        'objectMap' => [
            'first' => ['label' => 'alpha', 'count' => 1],
        ],
    ], MapDto::class);

    expect($dto->objectMap['first'])->toBeInstanceOf(TestObject::class);
    expect($dto->objectMap['first']->label)->toBe('alpha');
});

test('empty map with MapType maps to empty array', function () {
    $dto = DataMapper::toObject([
        'stringMap' => [],
        'objectMap' => [],
    ], MapDto::class);

    expect($dto->stringMap)->toBe([]);
    expect($dto->objectMap)->toBe([]);
});

test('MapType round-trip preserves values', function () {
    $input = [
        'stringMap' => ['k1' => 'v1'],
        'objectMap' => [
            'x' => ['label' => 'test', 'count' => 42],
        ],
    ];
    $dto = DataMapper::toObject($input, MapDto::class);
    $output = DataMapper::toArray($dto);

    expect($output)->toMatchArray($input);
});

// ============================================================
// Files
// ============================================================

test('maps UploadedFileInterface value directly when already an instance', function () {
    $mock = new MockUploadedFile();
    $dto = DataMapper::toObject(['file' => $mock], FileDto::class);

    expect($dto->file)->toBe($mock);
});

test('uses fileFactory when file is not UploadedFileInterface', function () {
    DataMapper::$fileFactory = new TestFileFactory();
    $dto = DataMapper::toObject(['file' => '/path/to/file.txt'], FileDto::class);

    expect($dto->file)->toBeInstanceOf(TestFile::class);
});

test('throws when file is not UploadedFileInterface and no fileFactory is set', function () {
    DataMapper::$fileFactory = null;

    expect(fn() => DataMapper::toObject(['file' => '/path/to/file.txt'], FileDto::class))
        ->toThrow(RuntimeException::class, 'No FileFactoryInterface set');
});

test('toArray returns file instances as-is', function () {
    $file = new \SplFileObject(__FILE__);
    $uploaded = new MockUploadedFile();

    $dto = new AllTypesDto();
    $ref = new ReflectionClass($dto);
    $ref->getProperty('file')->setValue($dto, $file);
    $ref->getProperty('uploadedFile')->setValue($dto, $uploaded);

    $output = DataMapper::toArray($dto);

    expect($output['file'])->toBe($file);
    expect($output['uploadedFile'])->toBe($uploaded);
});

// ============================================================
// Mixed / Object / Untyped
// ============================================================

test('maps mixed, object, and untyped properties', function () {
    $dto = DataMapper::toObject([
        'mixedValue' => 'anything',
        'objectValue' => ['key' => 'val'],
        'untyped' => 12345,
    ], MixedTypesDto::class);

    expect($dto->mixedValue)->toBe('anything');
    expect($dto->objectValue)->toBeInstanceOf(stdClass::class);
    expect($dto->objectValue->key)->toBe('val');
    expect($dto->untyped)->toBe(12345);
});

test('mixed property accepts null', function () {
    $dto = DataMapper::toObject([
        'mixedValue' => null,
        'objectValue' => ['a' => 1],
        'untyped' => null,
    ], MixedTypesDto::class);

    expect($dto->mixedValue)->toBeNull();
    expect($dto->untyped)->toBeNull();
});

test('mixed property accepts array', function () {
    $dto = DataMapper::toObject([
        'mixedValue' => ['x' => 1, 'y' => 2],
        'objectValue' => ['a' => 1],
        'untyped' => ['list'],
    ], MixedTypesDto::class);

    expect($dto->mixedValue)->toBe(['x' => 1, 'y' => 2]);
});

// ============================================================
// Readonly Properties
// ============================================================

test('maps readonly property via reflection', function () {
    $dto = DataMapper::toObject(['value' => 'readonly-test'], ReadonlyDto::class);

    expect($dto->value)->toBe('readonly-test');
});

// ============================================================
// Static Properties
// ============================================================

test('ignores static properties during mapping', function () {
    $original = StaticDto::$staticValue;

    $dto = DataMapper::toObject([
        'name' => 'test',
        'staticValue' => 'should-be-ignored',
    ], StaticDto::class);

    expect($dto->name)->toBe('test');
    expect(StaticDto::$staticValue)->toBe($original);
});

// ============================================================
// toArray Edge Cases
// ============================================================

test('toArray skips uninitialized properties', function () {
    $dto = DataMapper::toObject([], EmptyDto::class);
    $output = DataMapper::toArray($dto);

    expect($output)->not->toHaveKey('required');
    expect($output)->toHaveKey('optional');
    expect($output['optional'])->toBeNull();
});

test('toArray handles null values', function () {
    $dto = DataMapper::toObject([
        'name' => 'Test',
        'age' => 1,
        'nickname' => null,
        'score' => null,
    ], ScalarDto::class);
    $output = DataMapper::toArray($dto);

    expect($output['nickname'])->toBeNull();
    expect($output['score'])->toBeNull();
});

test('toArray skips static properties', function () {
    $dto = DataMapper::toObject(['name' => 'test'], StaticDto::class);
    $output = DataMapper::toArray($dto);

    expect($output)->toHaveKey('name');
    expect($output)->not->toHaveKey('staticValue');
});

test('toArray converts stdClass object to array', function () {
    $dto = new MixedTypesDto();
    $dto->objectValue = (object)['foo' => 'bar', 'baz' => 123];
    $dto->mixedValue = 'test';
    $dto->untyped = null;

    $output = DataMapper::toArray($dto);

    expect($output['objectValue'])->toBe(['foo' => 'bar', 'baz' => 123]);
});

// ============================================================
// Edge Cases: Scalar Boundaries
// ============================================================

test('maps boolean false correctly', function () {
    $dto = DataMapper::toObject([
        'flag' => false,
        'optionalFlag' => false,
        'count' => 0,
        'text' => '',
    ], BooleanEdgeCaseDto::class);

    expect($dto->flag)->toBeFalse();
    expect($dto->optionalFlag)->toBeFalse();
    expect($dto->count)->toBe(0);
    expect($dto->text)->toBe('');
});

test('maps empty string and zero correctly', function () {
    $dto = DataMapper::toObject([
        'name' => '',
        'age' => 0,
    ], ScalarDto::class);

    expect($dto->name)->toBe('');
    expect($dto->age)->toBe(0);
});

test('boolean false round-trip preserves value', function () {
    $input = ['flag' => false, 'optionalFlag' => null, 'count' => 0, 'text' => ''];
    $dto = DataMapper::toObject($input, BooleanEdgeCaseDto::class);
    $output = DataMapper::toArray($dto);

    expect($output)->toMatchArray($input);
});

test('maps float edge cases correctly', function () {
    $dto = DataMapper::toObject([
        'value' => 0.0,
        'nullable' => null,
    ], FloatEdgeCaseDto::class);

    expect($dto->value)->toBe(0.0);
    expect($dto->nullable)->toBeNull();
});

test('maps very large and very small floats', function () {
    $dto = DataMapper::toObject([
        'value' => PHP_FLOAT_MAX,
        'nullable' => PHP_FLOAT_MIN,
    ], FloatEdgeCaseDto::class);
    $output = DataMapper::toArray($dto);

    expect($output['value'])->toBe(PHP_FLOAT_MAX);
    expect($output['nullable'])->toBe(PHP_FLOAT_MIN);
});

test('maps negative integers', function () {
    $dto = DataMapper::toObject([
        'name' => 'Negative',
        'age' => -1,
        'score' => -999,
    ], ScalarDto::class);

    expect($dto->age)->toBe(-1);
    expect($dto->score)->toBe(-999);
});

// ============================================================
// Edge Cases: Deep Nesting
// ============================================================

test('maps deeply nested objects', function () {
    $dto = DataMapper::toObject([
        'name' => 'root',
        'level1' => [
            'title' => 'mid',
            'child' => ['label' => 'leaf', 'count' => 42],
        ],
    ], DeepNestedDto::class);

    expect($dto->name)->toBe('root');
    expect($dto->level1)->toBeInstanceOf(NestedDto::class);
    expect($dto->level1->title)->toBe('mid');
    expect($dto->level1->child)->toBeInstanceOf(TestObject::class);
    expect($dto->level1->child->label)->toBe('leaf');
    expect($dto->level1->child->count)->toBe(42);
});

test('deeply nested round-trip preserves values', function () {
    $input = [
        'name' => 'root',
        'level1' => [
            'title' => 'mid',
            'child' => ['label' => 'leaf', 'count' => 7],
        ],
    ];
    $dto = DataMapper::toObject($input, DeepNestedDto::class);
    $output = DataMapper::toArray($dto);

    expect($output['name'])->toBe('root');
    expect($output['level1']['title'])->toBe('mid');
    expect($output['level1']['child'])->toBe(['label' => 'leaf', 'count' => 7]);
});

test('deeply nested optional null preserves null', function () {
    $dto = DataMapper::toObject([
        'name' => 'root',
        'level1' => null,
    ], DeepNestedDto::class);

    expect($dto->level1)->toBeNull();
});

// ============================================================
// Edge Cases: Nested objects with extra keys
// ============================================================

test('nested object ignores unknown keys', function () {
    $dto = DataMapper::toObject([
        'title' => 'Test',
        'child' => [
            'label' => 'inner',
            'count' => 5,
            'unknownNested' => 'should-be-ignored',
        ],
    ], NestedDto::class);

    expect($dto->child->label)->toBe('inner');
    expect($dto->child->count)->toBe(5);
    expect(property_exists($dto->child, 'unknownNested'))->toBeFalse();
});

// ============================================================
// Edge Cases: Nullable Arrays
// ============================================================

test('maps null to nullable array with ArrayType', function () {
    $dto = DataMapper::toObject([
        'items' => null,
        'plainItems' => null,
    ], NullableArrayDto::class);

    expect($dto->items)->toBeNull();
    expect($dto->plainItems)->toBeNull();
});

test('maps values to nullable array with ArrayType', function () {
    $dto = DataMapper::toObject([
        'items' => [
            ['label' => 'a', 'count' => 1],
        ],
        'plainItems' => ['x', 'y'],
    ], NullableArrayDto::class);

    expect($dto->items)->toHaveCount(1);
    expect($dto->items[0])->toBeInstanceOf(TestObject::class);
    expect($dto->plainItems)->toBe(['x', 'y']);
});

test('nullable array round-trip preserves null', function () {
    $dto = DataMapper::toObject([
        'items' => null,
        'plainItems' => null,
    ], NullableArrayDto::class);
    $output = DataMapper::toArray($dto);

    expect($output['items'])->toBeNull();
    expect($output['plainItems'])->toBeNull();
});

// ============================================================
// Edge Cases: Enums
// ============================================================

test('maps null to nullable enum', function () {
    $dto = DataMapper::toObject([
        'status' => 'a',
        'optionalStatus' => null,
    ], EnumDto::class);

    expect($dto->status)->toBe(TestEnum::A);
    expect($dto->optionalStatus)->toBeNull();
});

test('toArray handles nullable enum with null value', function () {
    $dto = DataMapper::toObject([
        'status' => 'b',
        'optionalStatus' => null,
    ], EnumDto::class);
    $output = DataMapper::toArray($dto);

    expect($output['status'])->toBe('b');
    expect($output['optionalStatus'])->toBeNull();
});

// ============================================================
// Edge Cases: Multiple Readonly Properties
// ============================================================

test('maps multiple readonly properties', function () {
    $dto = DataMapper::toObject([
        'name' => 'readonly-name',
        'age' => 25,
        'mutable' => 'can-change',
    ], MultiReadonlyDto::class);

    expect($dto->name)->toBe('readonly-name');
    expect($dto->age)->toBe(25);
    expect($dto->mutable)->toBe('can-change');
});

test('readonly properties round-trip preserves values', function () {
    $input = ['name' => 'test', 'age' => 99, 'mutable' => 'val'];
    $dto = DataMapper::toObject($input, MultiReadonlyDto::class);
    $output = DataMapper::toArray($dto);

    expect($output)->toMatchArray($input);
});

// ============================================================
// Edge Cases: PHP 8.4 Asymmetric Visibility
// ============================================================

test('maps asymmetric visibility properties via reflection', function () {
    require_once __DIR__ . '/fixtures/dtos/AsymmetricVisibilityDto.php';
    $class = 'futuretek\\datamapper\\tests\\fixtures\\dtos\\AsymmetricVisibilityDto';
    $dto = DataMapper::toObject([
        'name' => 'asymmetric',
        'age' => 30,
        'nickname' => 'asym',
    ], $class);

    expect($dto->name)->toBe('asymmetric');
    expect($dto->age)->toBe(30);
    expect($dto->nickname)->toBe('asym');
})->skipOnPhp('<8.4.0');

test('asymmetric visibility round-trip preserves values', function () {
    require_once __DIR__ . '/fixtures/dtos/AsymmetricVisibilityDto.php';
    $class = 'futuretek\\datamapper\\tests\\fixtures\\dtos\\AsymmetricVisibilityDto';
    $input = ['name' => 'test', 'age' => 42, 'nickname' => null];
    $dto = DataMapper::toObject($input, $class);
    $output = DataMapper::toArray($dto);

    expect($output)->toMatchArray($input);
})->skipOnPhp('<8.4.0');

// ============================================================
// Edge Cases: Validation
// ============================================================

test('throws for multiple missing required fields with validation on', function () {
    DataMapper::$validateRequiredProperties = true;

    expect(fn() => DataMapper::toObject([], ScalarDto::class))
        ->toThrow(InvalidArgumentException::class, "Missing required property 'name'");
});

test('validation passes when all required fields are present', function () {
    DataMapper::$validateRequiredProperties = true;

    $dto = DataMapper::toObject([
        'name' => 'Valid',
        'age' => 1,
    ], ScalarDto::class);

    expect($dto->name)->toBe('Valid');
    expect($dto->age)->toBe(1);
});

test('validation allows nullable fields to be absent', function () {
    DataMapper::$validateRequiredProperties = true;

    $dto = DataMapper::toObject([
        'name' => 'Test',
        'age' => 5,
    ], ScalarDto::class);

    expect($dto->nickname)->toBeNull();
    expect($dto->score)->toBeNull();
});

// ============================================================
// Edge Cases: Mixed type variations
// ============================================================

test('mixed property accepts integer zero', function () {
    $dto = DataMapper::toObject([
        'mixedValue' => 0,
        'objectValue' => ['a' => 1],
        'untyped' => 0,
    ], MixedTypesDto::class);

    expect($dto->mixedValue)->toBe(0);
    expect($dto->untyped)->toBe(0);
});

test('mixed property accepts boolean false', function () {
    $dto = DataMapper::toObject([
        'mixedValue' => false,
        'objectValue' => ['a' => 1],
        'untyped' => false,
    ], MixedTypesDto::class);

    expect($dto->mixedValue)->toBeFalse();
    expect($dto->untyped)->toBeFalse();
});

test('mixed property accepts empty string', function () {
    $dto = DataMapper::toObject([
        'mixedValue' => '',
        'objectValue' => ['a' => 1],
        'untyped' => '',
    ], MixedTypesDto::class);

    expect($dto->mixedValue)->toBe('');
    expect($dto->untyped)->toBe('');
});

// ============================================================
// Edge Cases: ArrayType with single item and large arrays
// ============================================================

test('maps single-item array of objects', function () {
    $dto = DataMapper::toObject([
        'plain' => [],
        'ints' => [],
        'objects' => [
            ['label' => 'only', 'count' => 1],
        ],
    ], ArrayDto::class);

    expect($dto->objects)->toHaveCount(1);
    expect($dto->objects[0]->label)->toBe('only');
});

test('maps large array of objects', function () {
    $items = array_map(
        fn($i) => ['label' => "item-$i", 'count' => $i],
        range(0, 99)
    );

    $dto = DataMapper::toObject([
        'plain' => [],
        'ints' => [],
        'objects' => $items,
    ], ArrayDto::class);

    expect($dto->objects)->toHaveCount(100);
    expect($dto->objects[0]->label)->toBe('item-0');
    expect($dto->objects[99]->label)->toBe('item-99');
    expect($dto->objects[99]->count)->toBe(99);
});

// ============================================================
// Edge Cases: MapType with integer-like string keys
// ============================================================

test('maps object map with numeric string keys', function () {
    $dto = DataMapper::toObject([
        'stringMap' => ['0' => 'zero', '1' => 'one'],
        'objectMap' => [
            '100' => ['label' => 'numeric-key', 'count' => 100],
        ],
    ], MapDto::class);

    expect($dto->objectMap['100'])->toBeInstanceOf(TestObject::class);
    expect($dto->objectMap['100']->label)->toBe('numeric-key');
});

// ============================================================
// Edge Cases: toArray comprehensive
// ============================================================

test('toArray handles object with all null nullable properties', function () {
    $dto = DataMapper::toObject([
        'name' => 'test',
        'age' => 1,
        'nickname' => null,
        'score' => null,
    ], ScalarDto::class);
    $output = DataMapper::toArray($dto);

    expect($output)->toHaveKeys(['name', 'age', 'nickname', 'score']);
    expect($output['nickname'])->toBeNull();
    expect($output['score'])->toBeNull();
});

test('toArray with nested object containing default null properties', function () {
    $dto = DataMapper::toObject([
        'title' => 'Parent',
        'child' => ['label' => 'child', 'count' => 1],
    ], NestedDto::class);
    $output = DataMapper::toArray($dto);

    expect($output)->toHaveKey('optionalChild');
    expect($output['optionalChild'])->toBeNull();
});

test('toArray with deeply nested objects', function () {
    $dto = DataMapper::toObject([
        'name' => 'root',
        'level1' => [
            'title' => 'mid',
            'child' => ['label' => 'deep', 'count' => 3],
        ],
    ], DeepNestedDto::class);
    $output = DataMapper::toArray($dto);

    expect($output['level1']['child'])->toBe(['label' => 'deep', 'count' => 3]);
});

// ============================================================
// toArray with array of objects
// ============================================================

test('toArray converts array of objects to array of arrays', function () {
    $obj1 = DataMapper::toObject(['name' => 'Alice', 'age' => 30], ScalarDto::class);
    $obj2 = DataMapper::toObject(['name' => 'Bob', 'age' => 25], ScalarDto::class);

    $output = DataMapper::toArray([$obj1, $obj2]);

    expect($output)->toBeArray();
    expect($output)->toHaveCount(2);
    expect($output[0])->toBe(['name' => 'Alice', 'age' => 30, 'nickname' => null, 'score' => null]);
    expect($output[1])->toBe(['name' => 'Bob', 'age' => 25, 'nickname' => null, 'score' => null]);
});

test('toArray converts empty array to empty array', function () {
    $output = DataMapper::toArray([]);

    expect($output)->toBeArray();
    expect($output)->toBeEmpty();
});

test('toArray converts single-element array of objects', function () {
    $obj = DataMapper::toObject(['label' => 'only', 'count' => 1], TestObject::class);

    $output = DataMapper::toArray([$obj]);

    expect($output)->toHaveCount(1);
    expect($output[0])->toBe(['label' => 'only', 'count' => 1]);
});

test('toArray with array of nested objects', function () {
    $obj1 = DataMapper::toObject([
        'title' => 'Parent1',
        'child' => ['label' => 'child1', 'count' => 1],
    ], NestedDto::class);
    $obj2 = DataMapper::toObject([
        'title' => 'Parent2',
        'child' => ['label' => 'child2', 'count' => 2],
    ], NestedDto::class);

    $output = DataMapper::toArray([$obj1, $obj2]);

    expect($output)->toHaveCount(2);
    expect($output[0]['child'])->toBe(['label' => 'child1', 'count' => 1]);
    expect($output[1]['child'])->toBe(['label' => 'child2', 'count' => 2]);
});

test('toArray preserves string keys in input array', function () {
    $obj1 = DataMapper::toObject(['label' => 'first', 'count' => 1], TestObject::class);
    $obj2 = DataMapper::toObject(['label' => 'second', 'count' => 2], TestObject::class);

    $output = DataMapper::toArray(['a' => $obj1, 'b' => $obj2]);

    expect($output)->toHaveKeys(['a', 'b']);
    expect($output['a'])->toBe(['label' => 'first', 'count' => 1]);
    expect($output['b'])->toBe(['label' => 'second', 'count' => 2]);
});


