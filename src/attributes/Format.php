<?php

namespace futuretek\datamapper\attributes;

use Attribute;

/**
 * Marks a DateTimeInterface property with its OpenAPI `format`.
 *
 * Use this to indicate whether the date is:
 * - `date` (e.g., "2025-06-17")
 * - `date-time` (e.g., "2025-06-17T15:00:00Z")
 *
 * This helps the data mapper serialize/deserialize correctly.
 *
 * ## Usage
 *
 * ```
 * #[Format('date-time')]
 * public \DateTimeInterface $createdAt;
 *
 * #[Format('date')]
 * public \DateTimeInterface $birthDate;
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Format
{
    public function __construct(
        public string $type
    )
    {
    }
}