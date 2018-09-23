<?php

namespace Symfony\Component\Serializer\Normalizer\Instantiator;

use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Exception\RuntimeException;

final class DiscriminatorInstantiator implements InstantiatorInterface
{
    private $decorated;
    private $classDiscriminatorResolver;

    public function __construct(InstantiatorInterface $decorated, ClassDiscriminatorResolverInterface $classDiscriminatorResolver)
    {
        $this->decorated = $decorated;
        $this->classDiscriminatorResolver = $classDiscriminatorResolver;
    }

    public function instantiateObject(array &$data, string $class, array &$context, \ReflectionClass $reflectionClass)
    {
        if ($this->classDiscriminatorResolver && $mapping = $this->classDiscriminatorResolver->getMappingForClass($class)) {
            if (!isset($data[$mapping->getTypeProperty()])) {
                throw new RuntimeException(sprintf('Type property "%s" not found for the abstract object "%s"', $mapping->getTypeProperty(), $class));
            }

            $type = $data[$mapping->getTypeProperty()];
            if (null === ($mappedClass = $mapping->getClassForType($type))) {
                throw new RuntimeException(sprintf('The type "%s" has no mapped class for the abstract object "%s"', $type, $class));
            }

            $class = $mappedClass;
            $reflectionClass = new \ReflectionClass($class);
        }

        return $this->decorated->instantiateObject($data, $class, $context, $reflectionClass, $allowedAttributes, $format);
    }
}
