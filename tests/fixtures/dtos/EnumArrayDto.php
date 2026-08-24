<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

use futuretek\datamapper\attributes\ArrayType;

class EnumArrayDto
{
    #[ArrayType(TestEnum::class)]
    public array $statuses;

    #[ArrayType(TestEnum::class)]
    public ?array $optionalStatuses = null;
}
