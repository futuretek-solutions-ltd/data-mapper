<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

use futuretek\datamapper\attributes\ArrayType;

class ArrayDateDto
{
    #[ArrayType(DateDto::class)]
    public array $items;
}

