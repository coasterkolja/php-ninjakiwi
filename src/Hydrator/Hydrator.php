<?php

namespace Kan\NkOpendata\Hydrator;

use Kan\NkOpendata\Collections\Collection;
use Kan\NkOpendata\Hydrator\Attributes\CastWith;
use Kan\NkOpendata\Hydrator\Attributes\MapFrom;

class Hydrator
{
    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $data
     * @return T
     */
    public static function hydrate(string $class, array $data): object {
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class();
        }

        $args = [];
        
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $attributes = $param->getAttributes(MapFrom::class);

            if ($attributes) {
                $name = $attributes[0]->newInstance()->field;
            }

            if (!array_key_exists($name, $data) and (! $param->isOptional() or ! $param->isDefaultValueAvailable())) {
                throw new \InvalidArgumentException("Missing field: $name");
            }

            $value = $data[$name] ?? null;

            $args[] = self::cast($param, $value);
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<int, array<string, mixed>> $data
     * @return array<int, T>
     */
    public static function hydrateCollection(string $class, array $data): array {
        return array_map(
            fn($item) => self::hydrate($class, $item),
            $data
        );
    }

    private static function cast(\ReflectionParameter $param, mixed $value): mixed {
        $type = $param->getType();
        $attributes = $param->getAttributes(CastWith::class);

        if ($attributes) {
            $class = $attributes[0]->newInstance()->class;
            return (new $class())->cast($value);
        }

        if (!$type instanceof \ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        // Builtins
        if ($type->isBuiltin()) {
            return $value;
        }

        // DateTime
        if ($typeName === \DateTimeImmutable::class) {
            if (!is_int($value) && !is_float($value)) {
                throw new \UnexpectedValueException('Expected numeric timestamp for DateTime field');
            }

            return (new \DateTimeImmutable())->setTimestamp((int) ($value / 1000));
        }

        if (enum_exists($typeName)) {
            return self::castEnum($typeName, $value);
        }

        // Collection
        if (is_subclass_of($typeName, Collection::class)) {
            return self::castCollection($typeName, (array) $value);
        }

        // Nested DTO
        if (class_exists($typeName)) {
            /** @var array<string, mixed> $data */
            $data = (array) $value;

            return self::hydrate($typeName, $data);
        }

        return $value;
    }

    private static function castEnum(string $enumClass, mixed $value): mixed {
        if (!is_subclass_of($enumClass, \UnitEnum::class)) {
            return $value;
        }

        // backed enums (string/int)
        if (is_subclass_of($enumClass, \BackedEnum::class)) {
            if (!is_string($value) && !is_int($value)) {
                throw new \UnexpectedValueException('Expected string or int for enum value');
            }

            return $enumClass::tryFrom($value)
                ?? throw new \UnexpectedValueException("Invalid enum value: $value");
        }

        throw new \UnexpectedValueException("Non-backed enums are not supported for hydration");
    }

    /**
     * @template T of Collection
     * @param class-string<T> $collectionClass
     * @param array<mixed> $value
     * @return T
     */
    private static function castCollection(string $collectionClass, array $value): Collection {
        /** @var T $result */
        $result = new $collectionClass($value);

        return $result;
    }
}