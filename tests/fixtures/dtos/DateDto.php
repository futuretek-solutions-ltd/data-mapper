<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

use futuretek\datamapper\attributes\Format;

class DateDto
{
    #[Format('date')]
    public \DateTimeInterface $dateOnly;

    #[Format('date-time')]
    public \DateTimeInterface $dateTime;
}

