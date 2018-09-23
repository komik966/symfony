<?php

namespace Symfony\Component\Serializer\Normalizer\Instantiator;

interface InstantiatorInterface
{
    public function instantiateObject(array &$data, string $class, array &$context, \ReflectionClass $reflectionClass);
}
