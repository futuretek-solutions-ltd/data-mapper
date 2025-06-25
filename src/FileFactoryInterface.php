<?php

namespace futuretek\datamapper;

use Psr\Http\Message\UploadedFileInterface;

interface FileFactoryInterface
{
    /**
     * Create an UploadedFileInterface instance from file resource.
     * Implementation should handle all types of resources you will be using in your project.
     *
     * For example:
     * ```
     * function uploadFile(mixed $fileResource): UploadedFileInterface {
     *      //File path
     *      if (is_string($fileResource)) {
     *          $result = new UploadedFile();
     *          $fp = fopen($fileResource, 'r') || throw new \RuntimeException("Failed to open file: $fileResource");
     *          $result->setStream($fp);
     *          $result->setSize(filesize($fileResource));
     *          $result->setClientFilename(basename($fileResource));
     *          $result->setClientMediaType(mime_content_type($fileResource));
     *
     *          return $result;
     *      }
     *      throw new \InvalidArgumentException("Unsupported resource type: " . gettype($fileResource));
     * }
     * ```
     *
     * @param mixed $resource The file resource, can be a string (file path), SplFileObject, or other resource types.
     * @return UploadedFileInterface
     */
    public function createFromResource(mixed $resource): UploadedFileInterface;
}