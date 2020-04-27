<?php

namespace Metglobal\Compass\DTO;

interface CallableRequest extends Request
{
    public function call(...$args);
}
