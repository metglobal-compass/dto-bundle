<?php

namespace Metglobal\DTOBundle\Tests\Fixtures;

use Metglobal\DTOBundle\Annotation\PreSet;
use Metglobal\DTOBundle\Request;

class PreSetEventDefinedClass implements Request
{
    /**
     * @var string
     */
    public $testProperty;

    /**
     * @PreSet()
     */
    public function callBeforeParameters()
    {
        $this->testProperty = 'testValue';
    }
}
