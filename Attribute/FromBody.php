<?php

namespace Haskel\RequestParamBindBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromBody implements BindParamAttribute
{
    /**
     * json|xml|form-encoded|custom
     */
    public string $format;
}