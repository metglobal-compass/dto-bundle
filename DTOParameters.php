<?php

namespace Metglobal\DTOBundle;

interface DTOParameters
{
    const PROPERTY_SCOPE = 'scope';
    const PROPERTY_PATH = 'path';
    const PROPERTY_TYPE = 'type';
    const PROPERTY_NULLABLE = 'nullable';
    const PROPERTY_DISABLED = 'disabled';
    const PROPERTY_OPTIONS = 'options';
    const PROPERTY_UNDEFINEDABLE = 'undefinedable';

    const DEFAULT_TYPE = 'string';
    const DEFAULT_SCOPE = 'request';
    const DEFAULT_NULLABLE = true;
    const DEFAULT_DISABLED = false;
    const DEFAULT_OPTIONS = [];
    const DEFAULT_UNDEFINEDABLE = false;
}
