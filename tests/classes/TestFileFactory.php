<?php

namespace futuretek\datamapper\tests\classes;

use futuretek\datamapper\FileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

class TestFileFactory implements FileFactoryInterface
{
    public function createFromResource(mixed $resource): UploadedFileInterface
    {
        return new TestFile();
    }
}