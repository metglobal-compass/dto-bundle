<?php

namespace Metglobal\DTOBundle\Tests\Fixtures;

use Metglobal\DTOBundle\Annotation\DateParameter;
use Metglobal\DTOBundle\Annotation\PreSet;
use Metglobal\DTOBundle\Request;

class DateParameterDefinedClass implements Request
{
    /**
     * @DateParameter()
     *
     * @var \DateTime
     */
    public $testProperty;

    /**
     * @DateParameter(scope="query")
     *
     * @var \DateTime
     */
    public $testScopeProperty;

    /**
     * @DateParameter(path="testConfiguredPath")
     *
     * @var \DateTime
     */
    public $testPathProperty;

    /**
     * @DateParameter(format="m.d.Y")
     *
     * @var \DateTime
     */
    public $testFormatProperty;

    /**
     * @DateParameter(timezone="Europe/London")
     *
     * @var \DateTime
     */
    public $testTimezone;
}
