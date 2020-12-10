<?php

namespace Metglobal\DTOBundle\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "CLASS"})
 */
class Group
{
    /** @var string|null */
    public $target;

    /** @var bool */
    public $disabled = false;

    public function __construct(array $options)
    {
        $this->target = $options['value'] ?? $options['target'] ?? null;
    }
}
