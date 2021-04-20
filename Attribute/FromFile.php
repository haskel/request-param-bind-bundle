<?php

namespace Haskel\RequestParamBindBundle\Attribute;

use Attribute;
use Symfony\Component\HttpKernel\Attribute\ArgumentInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromFile implements BindParamAttribute, ArgumentInterface
{

}