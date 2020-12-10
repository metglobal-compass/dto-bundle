<?php

namespace Metglobal\DTOBundle\OptionsResolver;

use Metglobal\DTOBundle\DTOParamConverter;

class DateParameterOptionsResolver extends \Symfony\Component\OptionsResolver\OptionsResolver
{
    const PROPERTY_FORMAT = 'format';
    const PROPERTY_TIMEZONE = 'timezone';

    const DEFAULT_FORMAT = 'Y-m-d H:i:s';
    const DEFAULT_TIMEZONE = null;

    public function __construct()
    {
        $this->setDefaults(
            [
                self::PROPERTY_FORMAT => self::DEFAULT_FORMAT,
                self::PROPERTY_TIMEZONE => self::DEFAULT_TIMEZONE,
            ]
        );
    }
}
