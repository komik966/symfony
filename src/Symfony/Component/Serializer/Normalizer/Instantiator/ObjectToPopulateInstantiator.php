<?php

namespace Symfony\Component\Serializer\Normalizer\Instantiator;

use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;

final class ObjectToPopulateInstantiator implements InstantiatorInterface
{
    use ObjectToPopulateTrait;
    const OBJECT_TO_POPULATE = 'object_to_populate';

    private $decorated;

    public function __construct(InstantiatorInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function instantiateObject(array &$data, string $class, array &$context, \ReflectionClass $reflectionClass)
    {
        if (null !== $object = $this->extractObjectToPopulate($class, $context, static::OBJECT_TO_POPULATE)) {
            unset($context[static::OBJECT_TO_POPULATE]);

            return $object;
        }

        return $this->decorated->instantiateObject($data, $class, $context);
    }
}
