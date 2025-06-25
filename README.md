# DataMapper

A lightweight PHP 8.1+ utility for mapping associative arrays to plain PHP objects (POPOs) and vice versa, using reflection and PHP attributes.

## Features

- ⚙️ Supports native PHP 8.1+ typed properties
- 📌 Declarative mapping via custom attributes
- 📅 Handles date and datetime format conversions
- 🗂️ Supports arrays of objects via `#[ArrayType]`
- 🗺️ Supports associative maps via `#[MapType]`
- 📁 Supports file mapping via `SplFileObject` and `UploadedFileInterface`
- 🧩 Enum handling with `tryFrom`
- 🔍 Optional strict validation for required properties
- ✅ Converts object back to associative array (`toArray`)

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

    #[Format('date-time')]
    public \DateTimeImmutable $publishedAt;

    #[ArrayType(Comment::class)]
    public array $comments;

    #[MapType(valueType: Tag::class)]
    public array $tags;
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
DataMapper::$validateRequiredProperties = true;
DataMapper::$fileFactory = new MyFileFactory(); // for handling UploadedFileInterface
```

## Custom Attributes

- `#[Format('date')]` or `#[Format('date-time')]` – Parses strings into `DateTimeImmutable`
- `#[ArrayType(Foo::class)]` – Declares array of objects
- `#[MapType(valueType: Foo::class)]` – Declares map of string keys to objects

## File Handling

Supports any type of file types.
Also supports custom file factory for converting file types to PSR-7 `UploadedFileInterface`.

## License

Apache License 2.0
