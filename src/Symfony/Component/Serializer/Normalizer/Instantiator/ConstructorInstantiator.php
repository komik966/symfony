<?php

namespace Symfony\Component\Serializer\Normalizer\Instantiator;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class ConstructorInstantiator implements InstantiatorInterface
{
    private $decorated;
    private $nameConverter;

    public function __construct(InstantiatorInterface $decorated, NameConverterInterface $nameConverter = null)
    {
        $this->decorated = $decorated;
        $this->nameConverter = $nameConverter;
    }

    public function instantiateObject(array &$data, string $class, array &$context, \ReflectionClass $reflectionClass)
    {
        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            return new $class();
        }
        $constructorParameters = $constructor->getParameters();

        $params = array();
        foreach ($constructorParameters as $constructorParameter) {
            $paramName = $constructorParameter->name;
            $key = $this->nameConverter ? $this->nameConverter->normalize($paramName) : $paramName;

            $allowed = false === $allowedAttributes || \in_array($paramName, $allowedAttributes);
            $ignored = !$this->isAllowedAttribute($class, $paramName, $format, $context);
            if ($constructorParameter->isVariadic()) {
                if ($allowed && !$ignored && (isset($data[$key]) || array_key_exists($key, $data))) {
                    if (!\is_array($data[$paramName])) {
                        throw new RuntimeException(sprintf('Cannot create an instance of %s from serialized data because the variadic parameter %s can only accept an array.', $class, $constructorParameter->name));
                    }

                    $params = array_merge($params, $data[$paramName]);
                }
            } elseif ($allowed && !$ignored && (isset($data[$key]) || array_key_exists($key, $data))) {
                $parameterData = $data[$key];
                if (null === $parameterData && $constructorParameter->allowsNull()) {
                    $params[] = null;
                    // Don't run set for a parameter passed to the constructor
                    unset($data[$key]);
                    continue;
                }
                try {
                    if (null !== $constructorParameter->getClass()) {
                        if (!$this->serializer instanceof DenormalizerInterface) {
                            throw new LogicException(sprintf('Cannot create an instance of %s from serialized data because the serializer inject in "%s" is not a denormalizer', $constructorParameter->getClass(), static::class));
                        }
                        $parameterClass = $constructorParameter->getClass()->getName();
                        $parameterData = $this->serializer->denormalize($parameterData, $parameterClass, $format, $this->createChildContext($context, $paramName));
                    }
                } catch (\ReflectionException $e) {
                    throw new RuntimeException(sprintf('Could not determine the class of the parameter "%s".', $key), 0, $e);
                } catch (MissingConstructorArgumentsException $e) {
                    if (!$constructorParameter->getType()->allowsNull()) {
                        throw $e;
                    }
                    $parameterData = null;
                }

                // Don't run set for a parameter passed to the constructor
                $params[] = $parameterData;
                unset($data[$key]);
            } elseif (isset($context[static::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class][$key])) {
                $params[] = $context[static::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class][$key];
            } elseif ($constructorParameter->isDefaultValueAvailable()) {
                $params[] = $constructorParameter->getDefaultValue();
            } else {
                throw new MissingConstructorArgumentsException(
                    sprintf(
                        'Cannot create an instance of %s from serialized data because its constructor requires parameter "%s" to be present.',
                        $class,
                        $constructorParameter->name
                    )
                );
            }
        }

        if ($constructor->isConstructor()) {
            return $reflectionClass->newInstanceArgs($params);
        } else {
            return $constructor->invokeArgs(null, $params);
        }
    }
}
