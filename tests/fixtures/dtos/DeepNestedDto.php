<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

class DeepNestedDto
{
    public string $name;
    public ?NestedDto $level1 = null;
}

