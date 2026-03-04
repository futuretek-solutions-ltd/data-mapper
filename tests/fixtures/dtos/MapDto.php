<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

use futuretek\datamapper\attributes\MapType;

class MapDto
{
    #[MapType(valueType: 'string')]
    public array $stringMap;

    #[MapType(valueType: TestObject::class)]
    public array $objectMap;
}

