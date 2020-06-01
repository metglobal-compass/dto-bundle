<?php

namespace Metglobal\DTOBundle\Tests\Fixtures;

use Metglobal\DTOBundle\Annotation\PostSet;
use Metglobal\DTOBundle\Request;

class PostSetEventDefinedClass implements Request
{
    /**
     * @var string
     */
    public $testProperty;

    /**
     * @PostSet()
     */
    public function callAfterParameters()
    {
        $this->testProperty = [
            $this->testProperty
        ];
    }
}
