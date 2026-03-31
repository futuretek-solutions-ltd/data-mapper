<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

use futuretek\datamapper\attributes\ArrayType;

class ArrayStringDateDto
{
    #[ArrayType(\DateTimeInterface::class, format: 'date')]
    public array $holidays;

    #[ArrayType(\DateTimeInterface::class, format: 'date-time')]
    public array $events;
}

