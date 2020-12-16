<?php

namespace Metglobal\DTOBundle\Tests\Fixtures;

use Metglobal\DTOBundle\Annotation\Parameter;
use Metglobal\DTOBundle\Request;
use Metglobal\DTOBundle\Undefined;

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
     * @Parameter(type="int")
     *
     * @var int|null
     */
    public $testTypeProperty;

    /**
     * @Parameter(type="mixed")
     *
     * @var mixed
     */
    public $testMixedPath;

    /**
     * @Parameter(type="boolean")
     *
     * @var boolean
     */
    public $testBooleanProperty;

    /**
     * @Parameter(type="bool")
     *
     * @var boolean
     */
    public $testBoolProperty;

    /**
     * @Parameter(type="boolean")
     *
     * @var boolean
     */
    public $testBooleanWithDefaultProperty = true;

    /**
     * @Parameter(type="float")
     *
     * @var boolean
     */
    public $testFloatProperty;

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

    /**
     * @Parameter(type="date", undefined=true)
     * 
     * @var \DateTime
     */
    public $testDateProperty;
    
    /**
     * @Parameter(type="date", options={"format": "m.d.Y"})
     * 
     * @var \DateTime
     */
    public $testDateWithFormatProperty;
    
    /**
     * @Parameter(type="date", options={"timezone": "Europe/London"})
     * 
     * @var \DateTime
     */
    public $testDateWithTimeZoneProperty;

    /**
     * @Parameter(undefined=true)
     *
     * @var string|Undefined
     */
    public $testUndefinedableProperty;
}
