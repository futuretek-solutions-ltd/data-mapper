<?php

namespace futuretek\datamapper\attributes;

use Attribute;

/**
 * Declares the item type of array property, typically used for arrays of objects.
 *
 * This is needed because PHP does not support generics or array type hints directly.
 * The mapper uses this to instantiate array items correctly.
 *
 * ## Usage
 *
 * ```
 * #[ArrayType(User::class)]
 * public array $users;
 * ```
 *
 * This allows the mapper to do:
 * - `array_map(fn($item) => $this->map($item, User::class), $value)`
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayType
{
    public function __construct(
        public string $of
    )
    {
    }
}