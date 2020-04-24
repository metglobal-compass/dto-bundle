<?php

namespace Metglobal\Compass\DTO\Fixtures;

use Metglobal\Compass\DTO\Annotation\Parameter;
use Metglobal\Compass\DTO\Request;

/**
 * @Parameter(scope="query")
 *
 * Class ClassPropertyDefinedClass
 * @package Metglobal\Compass\DTO\Fixtures
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
