<?php

namespace Metglobal\Compass\DTO\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "CLASS"})
 */
final class Parameter
{
    /**
     * NOTE: Trying to convert array to any type is invalid
     * You can convert primitive types and string
     *
     * @var string
     */
    public $type;
    
    /**
     * @var string
     */
    public $scope;
    
    /**
     * @var string
     */
    public $path;

    /**
     * @var boolean
     */
    public $disabled;
}
