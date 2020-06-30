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
                DTOParamConverter::PROPERTY_OPTION_TYPE => DTOParamConverter::DEFAULT_OPTION_TYPE,
                DTOParamConverter::PROPERTY_OPTION_SCOPE => DTOParamConverter::DEFAULT_OPTION_SCOPE,
                DTOParamConverter::PROPERTY_OPTION_NULLABLE => DTOParamConverter::DEFAULT_OPTION_NULLABLE,
                DTOParamConverter::PROPERTY_OPTION_DISABLED => DTOParamConverter::DEFAULT_OPTION_DISABLED,
                self::PROPERTY_OPTION_FORMAT => self::DEFAULT_OPTION_FORMAT,
                self::PROPERTY_OPTION_TIMEZONE => self::DEFAULT_OPTION_TIMEZONE,
            ]
        );
        $this->setAllowedTypes(
            DTOParamConverter::PROPERTY_OPTION_NULLABLE, ['boolean', 'null']
        );
        $this->setAllowedValues(
            DTOParamConverter::PROPERTY_OPTION_SCOPE, ['request', 'query', 'headers', 'attributes', null]
        );
        $this->setRequired([DTOParamConverter::PROPERTY_OPTION_PATH]);
    }
}
