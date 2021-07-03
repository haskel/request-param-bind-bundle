<?php

namespace Haskel\RequestParamBindBundle;

interface NameConverter
{
    public function supports(string $name): bool;
    public function convert(string $name): string;
}