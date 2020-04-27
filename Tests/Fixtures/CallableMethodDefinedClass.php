<?php

namespace Metglobal\Compass\DTO\Tests\Fixtures;

use Metglobal\Compass\DTO\Annotation\Parameter;
use Metglobal\Compass\DTO\CallableRequest;
use Metglobal\Compass\DTO\Request;

class CallableMethodDefinedClass implements CallableRequest
{
    /**
     * @var string
     */
    public $testProperty;

    public function call(...$args)
    {
        $this->testProperty = $args[0];
    }
}
