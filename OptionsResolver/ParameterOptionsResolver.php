<?php

declare(strict_types=1);

namespace Metglobal\DTOBundle\OptionsResolver;

use Metglobal\DTOBundle\DTOParameters;

class ParameterOptionsResolver extends \Symfony\Component\OptionsResolver\OptionsResolver
{
    public function __construct()
    {
        $this->setDefaults(
            [
                DTOParameters::PROPERTY_TYPE => DTOParameters::DEFAULT_TYPE,
                DTOParameters::PROPERTY_SCOPE => DTOParameters::DEFAULT_SCOPE,
                DTOParameters::PROPERTY_NULLABLE => DTOParameters::DEFAULT_NULLABLE,
                DTOParameters::PROPERTY_DISABLED => DTOParameters::DEFAULT_DISABLED,
                DTOParameters::PROPERTY_OPTIONS => DTOParameters::DEFAULT_OPTIONS,
            ]
        );

        $this->setAllowedTypes(
            DTOParameters::PROPERTY_NULLABLE,
            ['boolean', 'null']
        );
        
        $this->setAllowedTypes(
            DTOParameters::PROPERTY_DISABLED,
            ['boolean', 'null']
        );
        
        $this->setAllowedTypes(
            DTOParameters::PROPERTY_OPTIONS,
            ['array']
        );
        
        $this->setAllowedValues(
            DTOParameters::PROPERTY_TYPE,
            ['date', 'float', 'string', 'boolean', 'bool', 'integer', 'int', 'mixed', null]
        );
        
        $this->setAllowedValues(
            DTOParameters::PROPERTY_SCOPE,
            ['request', 'query', 'headers', 'attributes', null]
        );

        $this->setRequired([DTOParameters::PROPERTY_PATH]);
    }
}
