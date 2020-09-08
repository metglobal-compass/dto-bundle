<?php

namespace Metglobal\DTOBundle\OptionsResolver;

use Metglobal\DTOBundle\DTOParamConverter;

class DateParameterOptionsResolver extends \Symfony\Component\OptionsResolver\OptionsResolver
{
    const PROPERTY_OPTION_FORMAT = 'format';
    const PROPERTY_OPTION_TIMEZONE = 'timezone';

    const DEFAULT_OPTION_FORMAT = 'Y-m-d H:i:s';
    const DEFAULT_OPTION_TIMEZONE = null;

    public function __construct()
    {
        $this->setDefaults(
            [
                self::PROPERTY_OPTION_FORMAT => self::DEFAULT_OPTION_FORMAT,
                self::PROPERTY_OPTION_TIMEZONE => self::DEFAULT_OPTION_TIMEZONE,
            ]
        );
    }
}
