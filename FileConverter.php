<?php

namespace Haskel\RequestParamBindBundle;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileConverter
{
    public function convert(UploadedFile $file);
}