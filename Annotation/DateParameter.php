<?php

namespace Metglobal\DTOBundle\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
final class DateParameter implements ParameterInterface
{
    /** @var string */
    public $type = 'date';

    /** @var string */
    public $scope;

    /** @var string */
    public $path;

    /** @var boolean */
    public $disabled;

    /** @var string */
    public $format = 'Y-m-d H:i:s';

    /**
     * Keeping this property null means use systems default timezone
     *
     * @var string
     */
    public $timezone;
}
