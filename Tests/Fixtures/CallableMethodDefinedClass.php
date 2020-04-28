<?php

namespace Metglobal\DTOBundle\Tests\Fixtures;

use Metglobal\DTOBundle\Annotation\Parameter;
use Metglobal\DTOBundle\CallableRequest;
use Metglobal\DTOBundle\Request;

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
