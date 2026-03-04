<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

class MultiReadonlyDto
{
    public readonly string $name;
    public readonly int $age;
    public ?string $mutable = null;
}

