<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

use futuretek\datamapper\attributes\ArrayType;

/**
 * Mirrors the real-world shape that exposed the enum-array bug in production (fls.test's
 * User.capabilities[].technologies): an ArrayType array of plain (non-enum) objects, where one of
 * those nested objects itself has an ArrayType array of enums. Nothing in the existing EnumArrayDto
 * coverage exercises this - it only tests a flat, top-level array of enums.
 */
class NestedEnumArrayDto
{
    #[ArrayType(EnumArrayDto::class)]
    public array $items;
}
