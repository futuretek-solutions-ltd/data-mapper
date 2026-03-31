<?php

namespace futuretek\datamapper;

use DateTimeInterface;
use DateTimeImmutable;
use futuretek\datamapper\attributes\MapType;
use Psr\Http\Message\UploadedFileInterface;
use ReflectionClass;
use ReflectionNamedType;
use futuretek\datamapper\attributes\Format;
use futuretek\datamapper\attributes\ArrayType;

final class DataMapper
{
    /**
     * Whether to validate required properties during mapping.
     * If true, an exception will be thrown if a required property is missing.
     * If false, missing required properties will be ignored.
     *
     * @var bool
     */
    public static bool $validateRequiredProperties = false;

    /**
     * Optional factory for converting file instance into PSR-7 UploadedFileInterface.
     *
     * If set, factory will be used in toObject() method to convert file instances of properties
     * typed as UploadedFileInterface, that are not already instances of that interface.
     *
     * If not set, an exception will be thrown if a file instanace of different type is encountered.
     *
     * @var FileFactoryInterface|null
     */
    public static ?FileFactoryInterface $fileFactory = null;

    /**
     * Convert an associative array to a typed object using attributes and property types.
     *
     * @param array $data Input data
     * @param class-string $class Fully qualified class name
     * @return object Mapped instance of the class
     */
    public static function toObject(array $data, string $class): object
    {
        $refClass = new ReflectionClass($class);
        $object = $refClass->newInstanceWithoutConstructor();

        foreach ($refClass->getProperties() as $property) {
            if (!$property->isPublic() || $property->isStatic()) {
                continue;
            }

            $name = $property->getName();
            $type = $property->getType();

            if ($type instanceof ReflectionNamedType) {
                $nullable = $type->allowsNull();
                $typeName = $type->getName();
            } else {
                $nullable = true;
                $typeName = 'mixed';
            }

            if (!array_key_exists($name, $data)) {
                if (self::$validateRequiredProperties && !$nullable) {
                    throw new \InvalidArgumentException("Missing required property '$name' for class $class");
                }
                continue;
            }

            $value = $data[$name];

            // Format: date or date-time (or any DateTimeInterface property)
            $formatAttr = $property->getAttributes(Format::class)[0] ?? null;
            $isDateTimeType = $typeName === DateTimeInterface::class
                || $typeName === DateTimeImmutable::class
                || $typeName === \DateTime::class
                || is_subclass_of($typeName, DateTimeInterface::class);

            if (($formatAttr || $isDateTimeType) && is_string($value)) {
                try {
                    $parsed = new DateTimeImmutable($value);
                } catch (\Exception) {
                    $parsed = null;
                }
                $property->setValue($object, $parsed);
                continue;
            }

            // ArrayType
            if ($typeName === 'array') {
                $arrayTypeAttr = $property->getAttributes(ArrayType::class)[0] ?? null;
                if ($arrayTypeAttr && is_array($value)) {
                    $arrayType = $arrayTypeAttr->newInstance();
                    $itemClass = $arrayType->of;
                    $itemFormat = $arrayType->format;

                    $isDateTimeItem = $itemClass === DateTimeInterface::class
                        || $itemClass === DateTimeImmutable::class
                        || $itemClass === \DateTime::class
                        || (class_exists($itemClass) && is_subclass_of($itemClass, DateTimeInterface::class));

                    if ($isDateTimeItem && $itemFormat !== null) {
                        $mapped = array_map(function ($item) {
                            if ($item === null) {
                                return null;
                            }
                            try {
                                return new DateTimeImmutable($item);
                            } catch (\Exception) {
                                return null;
                            }
                        }, $value);
                    } else {
                        $mapped = array_map(function ($item) use ($itemClass) {
                            if (class_exists($itemClass)) {
                                return self::toObject($item, $itemClass);
                            }
                            return $item;
                        }, $value);
                    }

                    $property->setValue($object, $mapped);
                    continue;
                }
            }

            // MapType
            $mapTypeAttr = $property->getAttributes(MapType::class)[0] ?? null;
            if ($mapTypeAttr && is_array($value)) {
                $valueType = $mapTypeAttr->newInstance()->valueType;
                if (class_exists($valueType)) {
                    $mapped = [];
                    foreach ($value as $k => $v) {
                        $mapped[$k] = self::toObject($v, $valueType);
                    }
                    $property->setValue($object, $mapped);
                } else {
                    $property->setValue($object, $value);
                }
                continue;
            }

            // File
            if ($typeName === UploadedFileInterface::class) {
                if ($value instanceof UploadedFileInterface) {
                    $property->setValue($object, $value);
                    continue;
                }

                if (!self::$fileFactory) {
                    throw new \RuntimeException('No FileFactoryInterface set in DataMapper configuration');
                }

                $property->setValue($object, self::$fileFactory->createFromResource($value));
                continue;
            }

            // Enum
            if (enum_exists($typeName)) {
                if ($value === null && $nullable) {
                    $property->setValue($object, null);
                    continue;
                }
                $enum = $typeName::tryFrom($value);
                if (!$enum) {
                    throw new \UnexpectedValueException("Invalid enum value '$value' for $typeName::$name");
                }
                $property->setValue($object, $enum);
                continue;
            }

            // Nested object
            if (class_exists($typeName) && is_array($value)) {
                $nested = self::toObject($value, $typeName);
                $property->setValue($object, $nested);
                continue;
            }

            if ($typeName === 'object' && is_array($value)) {
                $property->setValue($object, (object)$value);
                continue;
            }

            // Scalar
            $property->setValue($object, $value);
        }

        return $object;
    }

    /**
     * Convert a typed object (or an array of typed objects) into an associative array.
     *
     * @param object|array<object> $object
     * @return array
     */
    public static function toArray(object|array $object): array
    {
        if (is_array($object)) {
            return array_map(
                fn($item) => is_object($item) ? self::toArray($item) : $item,
                $object
            );
        }

        $refClass = new ReflectionClass($object);
        $result = [];

        foreach ($refClass->getProperties() as $property) {
            if (!$property->isPublic() || $property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            if (!$property->isInitialized($object)) {
                continue;
            }

            $value = $property->getValue($object);

            if ($value === null) {
                $result[$name] = null;
                continue;
            }

            // Format
            if ($value instanceof DateTimeInterface) {
                $formatAttr = $property->getAttributes(Format::class)[0] ?? null;
                $format = $formatAttr ? $formatAttr->newInstance()->type : 'date-time';
                $result[$name] = $value->format(
                    $format === 'date' ? 'Y-m-d' : DateTimeInterface::ATOM
                );
                continue;
            }

            // Files
            if ($value instanceof \SplFileObject || $value instanceof UploadedFileInterface) {
                $result[$name] = $value;
                continue;
            }

            // Enum
            if (is_object($value) && enum_exists(get_class($value))) {
                $result[$name] = $value->value;
                continue;
            }

            // ArrayType
            if (is_array($value)) {
                $arrayTypeAttr = $property->getAttributes(ArrayType::class)[0] ?? null;
                if ($arrayTypeAttr) {
                    $arrayType = $arrayTypeAttr->newInstance();
                    $itemFormat = $arrayType->format;

                    if ($itemFormat !== null) {
                        $phpFormat = $itemFormat === 'date' ? 'Y-m-d' : DateTimeInterface::ATOM;
                        $result[$name] = array_map(
                            fn($item) => $item instanceof DateTimeInterface ? $item->format($phpFormat) : $item,
                            $value
                        );
                    } else {
                        $result[$name] = array_map(
                            fn($item) => is_object($item) ? self::toArray($item) : $item,
                            $value
                        );
                    }
                    continue;
                }
            }

            // MapType
            $mapTypeAttr = $property->getAttributes(MapType::class)[0] ?? null;
            if ($mapTypeAttr && is_array($value)) {
                $valueType = $mapTypeAttr->newInstance()->valueType;
                if (class_exists($valueType)) {
                    $result[$name] = [];
                    foreach ($value as $k => $v) {
                        $result[$name][$k] = self::toArray($v);
                    }
                } else {
                    $result[$name] = $value;
                }
                continue;
            }

            // Object
            if (is_object($value)) {
                // stdClass stores properties dynamically; reflection returns nothing for it.
                // Cast to array to preserve all key/value pairs at this level.
                $result[$name] = $value instanceof \stdClass
                    ? (array) $value
                    : self::toArray($value);
                continue;
            }

            // Scalar
            $result[$name] = $value;
        }

        return $result;
    }
}
