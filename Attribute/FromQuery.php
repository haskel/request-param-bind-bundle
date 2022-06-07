<?php

namespace Haskel\RequestParamBindBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromQuery implements BindParamAttribute
{
    public ?string $format;

    public ?string $type;

    public function __construct(?string $format = null, ?string $type = null)
    {
        $this->format = $format;
        $this->type   = $type;
    }
}
