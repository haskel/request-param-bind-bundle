<?php

namespace Haskel\RequestParamBindBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ItemType
{
    public string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }
}