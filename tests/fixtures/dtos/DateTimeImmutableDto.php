<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

use futuretek\datamapper\attributes\Format;

class DateTimeImmutableDto
{
    #[Format('date')]
    public \DateTimeImmutable $dateOnly;

    #[Format('date-time')]
    public \DateTimeImmutable $dateTime;

    public \DateTimeImmutable $noFormat;
}

