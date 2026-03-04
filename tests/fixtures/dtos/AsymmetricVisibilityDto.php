<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

class AsymmetricVisibilityDto
{
    public private(set) string $name;
    public private(set) int $age;
    public private(set) ?string $nickname = null;
}

