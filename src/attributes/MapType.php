<?php

namespace futuretek\datamapper\attributes;

use Attribute;

/**
 * Declares a property as a map from scalar keys to scalar or object values.
 *
 * Example: array<string, MyObject>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class MapType
{
    public function __construct(
        public string $keyType = 'string',
        public string $valueType = 'string'
    )
    {
    }
}
