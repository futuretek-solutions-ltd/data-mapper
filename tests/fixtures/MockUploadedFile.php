<?php

namespace futuretek\datamapper\tests\fixtures;

class MockUploadedFile implements \Psr\Http\Message\UploadedFileInterface {
    public function getStream()
    {
        return fopen('php://temp', 'rb');
    }

    public function moveTo(string $targetPath)
    {
    }

    public function getSize()
    {
        return 12345;
    }

    public function getError()
    {
        return \UPLOAD_ERR_OK;
    }

    public function getClientFilename()
    {
        return 'mockfile.txt';
    }

    public function getClientMediaType()
    {
        return 'text/plain';
    }
}