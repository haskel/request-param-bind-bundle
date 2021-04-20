<?php

namespace Haskel\RequestParamBindBundle\Attribute;

use Attribute;
use Symfony\Component\HttpKernel\Attribute\ArgumentInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromQuery implements BindParamAttribute, ArgumentInterface
{
    public ?string $format;

    public ?string $type;

    public function __construct(?string $format = null, ?string $type = null)
    {
        $this->format = $format;
        $this->type   = $type;
    }
}
