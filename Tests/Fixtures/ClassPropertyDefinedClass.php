<?php

namespace Metglobal\DTOBundle\Tests\Fixtures;

use Metglobal\DTOBundle\Annotation\Parameter;
use Metglobal\DTOBundle\Request;

/**
 * @Parameter(scope="query")
 *
 * Class ClassPropertyDefinedClass
 * @package Metglobal\DTOBundle\Fixtures
 */
class ClassPropertyDefinedClass implements Request
{
    /**
     * @Parameter(scope="attributes")
     *
     * @var string|null
     */
    public $testScopeProperty;

    /**
     * We did not define scope attribute here, class parameter annotation must override it
     *
     * @Parameter(type="int")
     *
     * @var int
     */
    public $testAnotherDefinitionProperty;
}
