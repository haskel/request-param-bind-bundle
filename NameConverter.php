<?php

namespace Haskel\RequestParamBindBundle;

interface NameConverter
{
    public function convert(string $name);
}