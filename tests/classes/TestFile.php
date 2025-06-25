<?php

namespace futuretek\datamapper\tests\classes;

use Psr\Http\Message\UploadedFileInterface;

class TestFile implements UploadedFileInterface
{
    public function getStream()
    {
    }

    public function moveTo(string $targetPath)
    {
    }

    public function getSize()
    {
    }

    public function getError()
    {
    }

    public function getClientFilename()
    {
    }

    public function getClientMediaType()
    {
    }
}