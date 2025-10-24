<?php

namespace App\Traits;

use ReflectionException;
use ReflectionMethod;

trait ReturnType
{
    /**
     * @throws ReflectionException
     */
    public function getMethodReturnType(string $class, string $methodName): ?string
    {
        $instance = new $class;

        $reflectionMethod = new ReflectionMethod($instance, $methodName);

        $returnType = $reflectionMethod->getReturnType();

        return $returnType?->getName();
    }
}
