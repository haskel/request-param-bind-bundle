<?php

namespace Haskel\RequestParamBindBundle\Attribute;

use Attribute;
use Symfony\Component\HttpKernel\Attribute\ArgumentInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromBody implements BindParamAttribute, ArgumentInterface
{
    /**
     * json|xml|form-encoded|custom
     */
    public string $format;
}