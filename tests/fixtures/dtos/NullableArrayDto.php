<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

use futuretek\datamapper\attributes\ArrayType;

class NullableArrayDto
{
    #[ArrayType(TestObject::class)]
    public ?array $items = null;

    public ?array $plainItems = null;
}

