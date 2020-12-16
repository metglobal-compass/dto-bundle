<?php

namespace Metglobal\DTOBundle\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "CLASS"})
 */
final class Parameter implements ParameterInterface
{
    /**
     * NOTE: Trying to convert array to any type is invalid
     * You can convert the data into primitive types
     *
     * @var string
     */
    public $type;
    
    /** @var string */
    public $scope;

    /** @var string */
    public $path;

    /** @var boolean */
    public $disabled;

    /** @var array */
    public $options = [];

    /** @var bool */
    public $undefined;
}
