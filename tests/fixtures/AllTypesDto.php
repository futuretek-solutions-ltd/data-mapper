<?php

namespace futuretek\datamapper\tests\fixtures;

use futuretek\datamapper\attributes\ArrayType;
use futuretek\datamapper\attributes\Format;
use futuretek\datamapper\attributes\MapType;
use Psr\Http\Message\UploadedFileInterface;

class AllTypesDto
{
    // === Scalar types ===
    public string $string;
    public int $int;
    public float $float;
    public bool $bool;

    // === Nullable scalars ===
    public ?string $nullableString = null;
    public ?int $nullableInt = null;
    public ?float $nullableFloat = null;
    public ?bool $nullableBool = null;

    // === DateTime ===
    #[Format('date')]
    public \DateTimeInterface $dateOnly;

    #[Format('date-time')]
    public \DateTimeInterface $dateTime;

    // === Arrays ===
    public array $plainArray;

    #[ArrayType('int')]
    public array $intArray;

    #[ArrayType(TestObject::class)]
    public array $objectArray;

    // === Maps ===
    #[MapType(valueType: 'string')]
    public array $stringMap;

    #[MapType(valueType: TestObject::class)]
    public array $objectMap;

    // === Enums ===
    public TestEnum $enum;

    // === Objects ===
    public TestObject $nestedObject;

    // === Files ===
    public \SplFileInfo $file;

    public UploadedFileInterface $uploadedFile;

    // === Other PHP 8.1+ types ===
    public mixed $mixedValue;
    public iterable $iterableValue;
    public object $objectValue;

    // === Union type (not yet supported) ===
    //public int|string $unionValue;

    // === Readonly (cannot be set via reflection) ===
    public readonly string $readonlyValue;

    // === Static (should be ignored) ===
    public static string $staticValue = 'static';

    // === Uninitialized (should remain null or throw if required) ===
    public string $unsetValue;

    // === Class without type ===
    public $untagged;
}
