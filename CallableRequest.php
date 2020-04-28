<?php

namespace Metglobal\DTOBundle;

interface CallableRequest extends Request
{
    public function call(...$args);
}
