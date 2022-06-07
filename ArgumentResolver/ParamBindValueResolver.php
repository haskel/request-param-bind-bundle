<?php

namespace Haskel\RequestParamBindBundle\ArgumentResolver;

use Haskel\RequestParamBindBundle\Attribute\{
    BindParamAttribute,
    FromBody,
    FromCookie,
    FromFile,
    FromHeader,
    FromQuery,
    FromRoute,
    ItemType,
    Required,
};
use Haskel\RequestParamBindBundle\Exception\{
    ExtractionException,
    UnknownTypeException,
    UnsupportedConversionException,
};
use Haskel\RequestParamBindBundle\FileConverter;
use Haskel\RequestParamBindBundle\NameConverter;
use Symfony\Component\HttpFoundation\{File\UploadedFile, FileBag, HeaderBag, InputBag, ParameterBag, Request};
use JetBrains\PhpStorm\Pure;
use LogicException;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Generator;
use ReflectionClass;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use ValueError;

use function is_int;
use function is_string;

class ParamBindValueResolver implements ArgumentValueResolverInterface
{
    /** @var NameConverter[] */
    private array $nameConverters = [];

    /** @var FileConverter[] */
    private array $fileConverters = [];

    #[Pure]
    /** {@inheritdoc} */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        foreach ($argument->getAttributes() as $attribute) {
            if ($attribute instanceof BindParamAttribute) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownTypeException|UnsupportedConversionException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        $attributes = $argument->getAttributes();

        foreach ($attributes as $attribute) {
            yield from match (true) {
                $attribute instanceof FromQuery  => $this->extractValue($request->query, $argument),
                $attribute instanceof FromBody   => $this->extractValue($request->request, $argument),
                $attribute instanceof FromHeader => $this->extractValue($request->headers, $argument),
                $attribute instanceof FromCookie => $this->extractValue($request->cookies, $argument),
                $attribute instanceof FromFile   => $this->extractFile($request->files, $argument),
                $attribute instanceof FromRoute  => $this->extractPathValue($request, $argument),
                default                          => throw new UnsupportedConversionException("Unknown attribute. FromQuery, FromBody, FromHeader, FromCookie, FromFile are supported."),
            };
        }
    }

    public function addNameConverter(NameConverter $nameConverter): void
    {
        $this->nameConverters[] = $nameConverter;
    }

    public function addFileConverter(FileConverter $fileConverter): void
    {
        $this->fileConverters[] = $fileConverter;
    }

    private function extractFile(FileBag $bag, ArgumentMetadata $argument): Generator
    {
        $value = $this->tryGetValue($argument->getName(), $bag);

        if (!$value instanceof UploadedFile) {
            $fileConverter = $this->fileConverters[$argument->getType()] ?? null;
            if (!$fileConverter) {
                throw new UnsupportedConversionException(sprintf("File converter for type '%s' not defined.", $argument->getType()));
            }
            $value = $fileConverter->convert($value);
        }

        yield $value;
    }

    private function extractValue(ParameterBag|HeaderBag $bag, ArgumentMetadata $argument): Generator
    {
        $type = $argument->getType();

        switch (true) {
            case in_array($type, ["int", "integer", "string", "float", "boolean", "bool"]):
                $value = $this->tryGetValue($argument->getName(), $bag);
                yield $this->castScalar($value, $type);
                break;

            case $type === "array":
                yield $this->extractArray($bag, $argument);
                break;

            case $argument->isVariadic():
                yield from $this->extractVariadic($bag, $argument);
                break;

            case enum_exists($type):
//                $value = $bag->get($argument->getName());
//
//                if (null === $value) {
//                    yield null;
//
//                    return;
//                }
//
//                if (!is_int($value) && !is_string($value)) {
//                    throw new LogicException(sprintf('Could not resolve the "%s $%s" controller argument: expecting an int or string, got %s.', $argument->getType(), $argument->getName(), get_debug_type($value)));
//                }
//
//                /** @var class-string<\BackedEnum> $enumType */
//                $enumType = $argument->getType();
//
//                try {
//                    yield $enumType::from($value);
//                } catch (ValueError $error) {
//                    throw new ExtractionException(sprintf('Could not resolve the "%s $%s" controller argument: %s', $argument->getType(), $argument->getName(), $error->getMessage()), $error);
//                }
                yield from $this->extractEnum($bag, $argument);
                break;

            case class_exists($type):
                yield $this->fillObject($bag->all(), $type);
                break;

            default:
                throw new UnknownTypeException(sprintf("Unknown type: %s", $type));
        }
    }

    private function extractArray(ParameterBag|HeaderBag $bag, ArgumentMetadata $argument): array
    {
        $items = $this->tryGetValue($argument->getName(), $bag);
        $objects = [];
        $type = $argument->getAttribute()->type;

        foreach ($items as $item) {
            $objects[] = $this->fillObject($item, $type);
        }

        return $objects;
    }

    private function extractVariadic(ParameterBag|HeaderBag $bag, ArgumentMetadata $argument): Generator
    {
        $items = $this->tryGetValue($argument->getName(), $bag);
        $type = $argument->getType();

        foreach ($items as $item) {
            yield $this->fillObject($item, $type);
        }
    }

    private function tryGetValue(string $paramName, ParameterBag|HeaderBag $bag)
    {
        foreach ($this->tryName($paramName) as $name) {
            if (!$bag->has($name)) {
                continue;
            }

            return $bag->get($name);
        }

        return null;
    }

    private function fillObject(array $parameters, string $type): object
    {
        $object = new $type;

        $classRef = new ReflectionClass($type);
        foreach ($classRef->getProperties() as $property) {
            $hasValue = array_key_exists($property->getName(), $parameters);
            $value = $parameters[$property->getName()] ?? null;

            foreach ($property->getAttributes() as $reflectionAttribute) {
                $attribute = $reflectionAttribute->newInstance();

                if ($attribute instanceof Required && !$hasValue && !$property->getDefaultValue()) {
                    throw new ExtractionException(sprintf("Property '%s->%s' marked as required and should be filled or should have default value", $type, $property->getName()));
                }

                if ($attribute instanceof ItemType
                    && $hasValue
                    && $property->getType()?->getName() === 'array') {
                    $items = [];
                    foreach ($value as $item) {
                        $items[] = $this->fillObject($item, $attribute->type);
                    }

                    $value = $items;
                }
            }

            $object->{$property->getName()} = $value;
        }

        return $object;
    }

    private function castScalar($value, string $type): float|bool|int|string
    {
        return match($type) {
            "int", "integer"  => (int) $value,
            "float"           => (float) $value,
            "string"          => (string) $value,
            "boolean", "bool" => (bool) $value,
        };
    }

    private function tryName(string $name): Generator
    {
        foreach ($this->nameConverters as $converter) {
            if (!$converter->supports($name)) {
                continue;
            }

            yield $converter->convert($name);
        }

        yield $name;
    }

    private function extractPathValue(Request $request, ArgumentMetadata $argument)
    {
        yield '';
    }

    private function extractEnum(ParameterBag|HeaderBag $bag, ArgumentMetadata $argument)
    {
        $value = $bag->get($argument->getName());

        if (null === $value) {
            yield null;

            return;
        }

        if (!is_int($value) && !is_string($value)) {
            throw new LogicException(sprintf('Could not resolve the "%s $%s" controller argument: expecting an int or string, got %s.', $argument->getType(), $argument->getName(), get_debug_type($value)));
        }

        /** @var class-string<\BackedEnum> $enumType */
        $enumType = $argument->getType();

        try {
            yield $enumType::from($value);
        } catch (ValueError $error) {
            throw new ExtractionException(sprintf('Could not resolve the "%s $%s" controller argument: %s', $argument->getType(), $argument->getName(), $error->getMessage()), $error);
        }
    }
}