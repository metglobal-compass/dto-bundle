<?php

namespace Metglobal\Compass\DTO\Exception;

use LogicException;
use Throwable;

class DTOException extends LogicException
{
    public function __construct($message = '', Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
