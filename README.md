# DataMapper

A lightweight PHP 8.4+ utility for mapping associative arrays to plain PHP objects (POPOs) and vice versa, using reflection and PHP attributes.

## Features

- ⚙️ Supports native PHP 8.4+ typed properties (scalars, nullable, `mixed`, `object`, `iterable`)
- 📌 Declarative mapping via custom attributes
- 📅 Handles date and datetime format conversions via `#[Format]`
- 🗂️ Supports typed arrays of objects via `#[ArrayType]`
- 📅 Supports arrays of date / date-time strings via `#[ArrayType(\DateTimeInterface::class, format: '...')]`
- 🗺️ Supports associative maps via `#[MapType]`
- 📁 Supports file mapping via `SplFileObject` and PSR-7 `UploadedFileInterface`
- 🧩 Backed enum handling with `tryFrom`
- 🔄 Nested object mapping (recursive)
- 🔒 Readonly property support via reflection
- 🔐 PHP 8.4 asymmetric visibility support (e.g., `public private(set)`)
- 🔍 Optional strict validation for required properties
- ✅ Converts objects back to associative arrays (`toArray`)
- 🚫 Skips non-public and static properties
- 💪 Gracefully handles uninitialized properties in `toArray`

## Installation

```bash
composer require futuretek/data-mapper
```

## Usage

### Define DTO with Attributes

```php
use futuretek\datamapper\attributes\ArrayType;
use futuretek\datamapper\attributes\MapType;
use futuretek\datamapper\attributes\Format;

class BlogPost
{
    public string $title;
    public ?string $subtitle = null;

    #[Format('date-time')]
    public \DateTimeImmutable $publishedAt;

    #[ArrayType(Comment::class)]
    public array $comments;

    #[ArrayType(\DateTimeInterface::class, format: 'date')]
    public array $holidays;

    #[MapType(valueType: Tag::class)]
    public array $tags;

    public StatusEnum $status;
    public Author $author;

    public readonly string $slug;
}
```

### Map From Array

```php
use futuretek\datamapper\DataMapper;

$dto = DataMapper::toObject($dataArray, BlogPost::class);
```

### Convert Back To Array

```php
$array = DataMapper::toArray($dto);
```

## Configuration

```php
// Throw InvalidArgumentException when a non-nullable property is missing from input
DataMapper::$validateRequiredProperties = true;

// Set a factory for converting file resources to PSR-7 UploadedFileInterface
DataMapper::$fileFactory = new MyFileFactory();
```

## Custom Attributes

### `#[Format]`

Parses date strings into `DateTimeImmutable` instances.

```php
#[Format('date')]        // Parses "2025-06-17" using Y-m-d format
public \DateTimeInterface $birthDate;

#[Format('date-time')]   // Parses "2025-06-17T15:00:00+00:00" using ISO 8601 / ATOM format
public \DateTimeInterface $createdAt;
```

When converting back via `toArray`, a `DateTimeInterface` property without `#[Format]` defaults to `date-time` format.

### `#[ArrayType]`

Declares the item type of an array property — supports class names, scalar type names, and `DateTimeInterface` with a `format`.

```php
#[ArrayType(Comment::class)]   // Array of objects — each item is recursively mapped
public array $comments;

#[ArrayType('int')]            // Array of scalars — items are passed through as-is
public array $scores;

#[ArrayType(\DateTimeInterface::class, format: 'date')]
public array $holidays;       // Array of "Y-m-d" strings ↔ DateTimeImmutable[]

#[ArrayType(\DateTimeInterface::class, format: 'date-time')]
public array $events;         // Array of ISO 8601 strings ↔ DateTimeImmutable[]
```

When `format` is provided:
- **`toObject`** — each string item is parsed into a `DateTimeImmutable`; an unparseable item becomes `null`.
- **`toArray`** — each `DateTimeInterface` item is formatted back to a string (`Y-m-d` for `'date'`, ATOM for `'date-time'`).

### `#[MapType]`

Declares a property as an associative map (string keys to typed values).

```php
#[MapType(valueType: Tag::class)]    // Map of objects — values are recursively mapped
public array $tags;

#[MapType(valueType: 'string')]      // Map of scalars — values are passed through as-is
public array $translations;
```

## Supported Property Types

| Type | `toObject` Behavior | `toArray` Behavior |
|------|--------------------|--------------------|
| `string`, `int`, `float`, `bool` | Assigned directly | Returned as-is |
| Nullable (`?type`) | `null` values accepted; missing keys use default | `null` returned |
| `DateTimeInterface` + `#[Format]` | Parsed from string via `new DateTimeImmutable()` | Formatted to string |
| `array` + `#[ArrayType(ClassName)]` | Items mapped recursively | Items converted recursively |
| `array` + `#[ArrayType(\DateTimeInterface::class, format: 'date\|date-time')]` | Each string item parsed into `DateTimeImmutable` | Each item formatted back to string |
| `array` + `#[MapType]` | Values mapped recursively if class type | Values converted recursively |
| Backed `enum` | Resolved via `tryFrom()` | Serialized to backing value |
| Nested class | Recursively mapped from sub-array | Recursively converted to sub-array |
| `object` | Cast from array via `(object)` | JSON encode/decode to array |
| `mixed` | Assigned as-is | Returned as-is |
| `UploadedFileInterface` | Assigned directly or via `$fileFactory` | Returned as-is |
| `SplFileObject` | Assigned directly | Returned as-is |
| `readonly` | Set via reflection | Returned normally |
| `public private(set)` | Set via reflection | Returned normally |
| Untyped (`public $x`) | Assigned as-is | Returned as-is |

## Property Handling Rules

- **Non-public properties** (private, protected) are skipped.
- **Static properties** are always skipped.
- **Unknown keys** in the input array are silently ignored.
- **Missing non-nullable properties** with `$validateRequiredProperties = true` throw `InvalidArgumentException`.
- **Missing non-nullable properties** with `$validateRequiredProperties = false` remain uninitialized.
- **Uninitialized properties** are skipped during `toArray` conversion.
- **Invalid enum values** throw `UnexpectedValueException`.
- **Malformed date strings** result in `null` (via `DateTimeImmutable::createFromFormat` returning `false`).

## File Handling

Supports `SplFileObject` and PSR-7 `UploadedFileInterface` properties.

For `UploadedFileInterface` properties, if the input value is already an `UploadedFileInterface` instance, it is assigned directly. Otherwise, the configured `FileFactoryInterface` is used to convert the value. If no factory is configured, a `RuntimeException` is thrown.

### Implementing FileFactoryInterface

```php
use futuretek\datamapper\FileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

class MyFileFactory implements FileFactoryInterface
{
    public function createFromResource(mixed $resource): UploadedFileInterface
    {
        if (is_string($resource)) {
            // Handle file path
            $stream = fopen($resource, 'r');
            return new MyUploadedFile($stream, filesize($resource), basename($resource));
        }

        throw new \InvalidArgumentException('Unsupported resource type: ' . gettype($resource));
    }
}
```

## Limitations

- **Union types** (e.g., `int|string`) are not supported.
- **Intersection types** are not supported.
- The constructor is **bypassed** via `newInstanceWithoutConstructor()` — constructor logic will not run.
- `DateTimeImmutable` is always used for date parsing, regardless of whether the property type is `DateTime` or `DateTimeInterface`.

## License

Apache License 2.0
