<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

use futuretek\datamapper\attributes\ArrayType;

class ArrayDto
{
    public array $plain;

    #[ArrayType('int')]
    public array $ints;

    #[ArrayType(TestObject::class)]
    public array $objects;
}
