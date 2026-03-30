<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

class NestedDateDto
{
    public string $name;
    public DateDto $child;
    public ?NullableDateDto $optionalChild = null;
}

