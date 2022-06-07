<?php

namespace Haskel\RequestParamBindBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromFile implements BindParamAttribute
{

}