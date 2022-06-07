<?php

namespace Haskel\RequestParamBindBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromRoute implements BindParamAttribute
{
    /**
     * json|xml|form-encoded|custom
     */
    public string $format;
}