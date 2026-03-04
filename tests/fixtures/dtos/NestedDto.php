<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

class NestedDto
{
    public string $title;
    public TestObject $child;
    public ?TestObject $optionalChild = null;
}

