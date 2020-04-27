<?php

namespace Metglobal\Compass\DTO\Tests\Fixtures;

use Metglobal\Compass\DTO\Annotation\Parameter;
use Metglobal\Compass\DTO\Request;

class PropertyDefinedClass implements Request
{
    /**
     * @Parameter()
     *
     * @var string|null
     */
    public $testProperty;

    /**
     * @Parameter(scope="attributes")
     *
     * @var string|null
     */
    public $testScopeProperty;

    /**
     * @Parameter()
     *
     * @var string|null
     */
    public $testNotNullableProperty = 'defaultValue';

    /**
     * @var string|null
     */
    public $testDefaultValueProperty = 'defaultValueWithoutAnnotation';

    /**
     * @Parameter(path="examplePath")
     *
     * @var string|null
     */
    public $testPathProperty;

    /**
     * @Parameter(path="[exampleExpressionPath][exampleChild]")
     *
     * @var string|null
     */
    public $testPathWithExpressionProperty;

    /**
     * @Parameter(type="int")
     *
     * @var int|null
     */
    public $testTypeProperty;

    /**
     * @Parameter(type="boolean")
     *
     * @var boolean
     */
    public $testBooleanProperty;

    /**
     * @Parameter(type="boolean")
     *
     * @var boolean
     */
    public $testBooleanWithDefaultProperty = true;

    /**
     * @Parameter(disabled=true)
     *
     * @var boolean
     */
    public $testDisabledProperty;

    /**
     * @Parameter(scope="headers")
     *
     * @var string
     */
    public $testHeaderProperty;
}
