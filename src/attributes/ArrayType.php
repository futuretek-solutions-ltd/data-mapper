<?php

namespace futuretek\datamapper\attributes;

use Attribute;

/**
 * Declares the item type of array property, typically used for arrays of objects or dates.
 *
 * This is needed because PHP does not support generics or array type hints directly.
 * The mapper uses this to instantiate array items correctly.
 *
 * ## Usage — array of objects
 *
 * ```
 * #[ArrayType(User::class)]
 * public array $users;
 * ```
 *
 * ## Usage — array of dates
 *
 * ```
 * #[ArrayType(\DateTimeInterface::class, format: 'date')]
 * public array $holidays;
 *
 * #[ArrayType(\DateTimeInterface::class, format: 'date-time')]
 * public array $events;
 * ```
 *
 * When `format` is set the mapper will:
 * - deserialize each string item into `\DateTimeImmutable`
 * - serialize each `\DateTimeInterface` item back to a string using the given format
 *   (`Y-m-d` for `'date'`, `\DateTimeInterface::ATOM` for `'date-time'`)
 *
 * @param string      $of     Fully-qualified class name or `\DateTimeInterface::class`
 * @param string|null $format `'date'` | `'date-time'` (only relevant for DateTime types)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayType
{
    public function __construct(
        public string $of,
        public ?string $format = null,
    )
    {
    }
}