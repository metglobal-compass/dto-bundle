<?php

namespace Metglobal\DTOBundle\OptionsResolver;

use Metglobal\DTOBundle\DTOParamConverter;

class ParameterOptionsResolver extends \Symfony\Component\OptionsResolver\OptionsResolver
{
    public function __construct()
    {
        $this->setDefaults(
            [
                DTOParamConverter::PROPERTY_OPTION_TYPE => DTOParamConverter::DEFAULT_OPTION_TYPE,
                DTOParamConverter::PROPERTY_OPTION_SCOPE => DTOParamConverter::DEFAULT_OPTION_SCOPE,
                DTOParamConverter::PROPERTY_OPTION_NULLABLE => DTOParamConverter::DEFAULT_OPTION_NULLABLE,
                DTOParamConverter::PROPERTY_OPTION_DISABLED => DTOParamConverter::DEFAULT_OPTION_DISABLED,
                DTOParamConverter::PROPERTY_OPTION_OPTIONS => DTOParamConverter::DEFAULT_OPTION_OPTIONS,
            ]
        );
        $this->setAllowedTypes(
            DTOParamConverter::PROPERTY_OPTION_NULLABLE,
            ['boolean', 'null']
        );
        
        $this->setAllowedTypes(
            DTOParamConverter::PROPERTY_OPTION_DISABLED,
            ['boolean', 'null']
        );
        
        $this->setAllowedTypes(
            DTOParamConverter::PROPERTY_OPTION_OPTIONS,
            ['array']
        );
        
        $this->setAllowedValues(
            DTOParamConverter::PROPERTY_OPTION_TYPE,
            ['date', 'float', 'string', 'boolean', 'bool', 'integer', 'int', 'mixed', null]
        );
        
        $this->setAllowedValues(
            DTOParamConverter::PROPERTY_OPTION_SCOPE,
            ['request', 'query', 'headers', 'attributes', null]
        );

        $this->setRequired([DTOParamConverter::PROPERTY_OPTION_PATH]);
    }
}
