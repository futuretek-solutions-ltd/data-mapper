<?php

namespace futuretek\datamapper\tests\fixtures\dtos;

use futuretek\datamapper\attributes\Format;

class NullableDateDto
{
    #[Format('date')]
    public ?\DateTimeInterface $dateOnly = null;

    #[Format('date-time')]
    public ?\DateTimeInterface $dateTime = null;
}

