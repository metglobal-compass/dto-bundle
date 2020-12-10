<?php

namespace Metglobal\DTOBundle\Tests\Fixtures;

use Metglobal\DTOBundle\Annotation\Group;
use Metglobal\DTOBundle\Annotation\Parameter;
use Metglobal\DTOBundle\Request;

/**
 * @Group("groupTarget")
 */
class GroupDefinedClass implements Request
{
    /** @var string */
    public $simpleProperty;

    /**
     * @var string
     *
     * @Group(target="nextTarget")
     */
    public $annotationDefinedProperty;

    /**
     * @var string
     *
     * @Group(disabled=true)
     */
    public $disabledGroupProperty;

    /**
     * @var string
     *
     * @Parameter(disabled=true)
     */
    public $parameterDisabledProperty;

    /** @var array */
    public $groupTarget;
}
