<?php

namespace Metglobal\DTOBundle;

use Doctrine\Common\Annotations\Reader;
use Metglobal\DTOBundle\Annotation\DateParameter;
use Metglobal\DTOBundle\Annotation\Group;
use Metglobal\DTOBundle\Annotation\Parameter;
use Metglobal\DTOBundle\Annotation\ParameterInterface;
use Metglobal\DTOBundle\Annotation\PostSet;
use Metglobal\DTOBundle\Annotation\PreSet;
use Metglobal\DTOBundle\Exception\DTOException;
use Metglobal\DTOBundle\OptionsResolver\DateParameterOptionsResolver;
use Metglobal\DTOBundle\OptionsResolver\ParameterOptionsResolver;
use Metglobal\DTOBundle\Request as RequestDTO;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Throwable;

final class DTOParamConverter implements ParamConverterInterface
{
    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var Reader */
    protected $annotationReader;

    /** @var ParameterOptionsResolver */
    protected $parameterOptionsResolver;

    /** @var array|OptionsResolver[] */
    protected $optionsResolvers = [];

    /** @var array|callable[] */
    protected $castCallbacks = [];

    public function __construct(PropertyAccessorInterface $propertyAccessor, Reader $annotationReader)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->annotationReader = $annotationReader;
        $this->parameterOptionsResolver = new ParameterOptionsResolver();

        // Define custom option resolvers here to resolve type options
        $this->optionsResolvers['date'] = new DateParameterOptionsResolver();

        // Define custom callbacks to parse values into different types
        $this->castCallbacks['date'] = function ($date, array $parameters) {
            if ($date === null) {
                return null;
            }

            $format = $parameters[DTOParameters::PROPERTY_OPTIONS][DateParameterOptionsResolver::PROPERTY_FORMAT];
            $timezone = $parameters[DTOParameters::PROPERTY_OPTIONS][DateParameterOptionsResolver::PROPERTY_TIMEZONE];

            if ($timezone) {
                return \DateTime::createFromFormat($format, $date, new \DateTimeZone($timezone));
            }

            return \DateTime::createFromFormat($format, $date);
        };

        $this->castCallbacks['mixed'] = function ($value, array $parameters) {
            // Mixed types are not applies any casting
            return $value;
        };

        $booleanCastCallback = function ($value, array $parameters) {
            /**
             * Boolean type is exceptional
             *
             * @See: https://www.w3schools.com/php/filter_validate_boolean.asp
             */
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        };

        $this->castCallbacks['bool'] = $booleanCastCallback;
        $this->castCallbacks['boolean'] = $booleanCastCallback;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        try {
            $class = $configuration->getClass();
            $instance = new $class();
            $request->attributes->set($configuration->getName(), $instance);

            $reflectionClass = new ReflectionClass($instance);
            $this->callEvent($reflectionClass, $instance, PreSet::class);

            $groups = $this->findGroups($reflectionClass);

            foreach ($this->getProperties($instance, $reflectionClass) as $parameterName => $parameters) {
                // Do not inject group properties
                if (array_key_exists($parameterName, $groups)) {
                    continue;
                }

                $scope = $parameters[DTOParameters::PROPERTY_SCOPE];
                $path = $parameters[DTOParameters::PROPERTY_PATH];

                // If parameter is defined as "undefinedable", means this parameter is not required in body/qs
                // Set a new Undefined() instance into request
                if (
                    $parameters[DTOParameters::PROPERTY_UNDEFINEDABLE] === true
                    && $this->isDefined($request, $scope, $path) === false
                ) {
                    $this->propertyAccessor->setValue($instance, $parameterName, new Undefined);

                    continue;
                }

                $value = $this->getValue(
                    $request,
                    $scope,
                    $path
                );

                $value = $this->castValue($value, $parameters);

                if (
                    $value !== null
                    || ($value === null && $parameters[DTOParameters::PROPERTY_NULLABLE] === true)
                ) {
                    $this->propertyAccessor->setValue($instance, $parameterName, $value);
                }
            }

            $this->groupProperties($groups, $instance);
            $this->callEvent($reflectionClass, $instance, PostSet::class);
        } catch (Throwable $e) {
            throw new DTOException('An error occurred while setting parameters into DTO.', $e);
        }

        return true;
    }

    protected function callEvent(ReflectionClass $reflectionClass, RequestDTO $instance, string $eventClass)
    {
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $annotation = $this->annotationReader->getMethodAnnotation($reflectionMethod, $eventClass);

            if ($annotation === null) {
                continue;
            }

            $reflectionMethod->invoke($instance);
        }
    }

    protected function groupProperties(array $groups, RequestDTO $instance)
    {
        foreach ($groups as $groupName => $relatedProperties) {
            $values = [];

            /** @var ReflectionProperty $property */
            foreach ($relatedProperties as $property) {
                $propertyName = $property->getName();

                if (array_key_exists($propertyName, $groups) === true) {
                    // If property is group property, skip
                    continue;
                }

                $values[$propertyName] = $this->propertyAccessor->getValue($instance, $propertyName);
            }

            $instance->{$groupName} = $values;
        }
    }

    protected function findGroups(ReflectionClass $reflectionClass): array
    {
        /** @var Group $classAnnotation */
        $classAnnotation = $this->annotationReader->getClassAnnotation($reflectionClass, Group::class);
        $classGroup = null;

        if ($classAnnotation !== null && $classAnnotation->disabled !== true) {
            $classGroup = $classAnnotation->target;
        }

        $groups = [];

        foreach ($reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $group = $classGroup;

            /** @var Group $annotation */
            $annotation = $this->annotationReader->getPropertyAnnotation($property, Group::class);

            if ($annotation !== null && $annotation->disabled === true) {
                $group = null;
            } elseif ($annotation !== null) {
                $group = $annotation->target;
            }

            if (empty($group)) {
                continue;
            }

            $groups[$group][] = $property;
        }

        return $groups;
    }

    /**
     * @param RequestDTO $dto
     * @return array
     * @throws ReflectionException
     */
    protected function getProperties(RequestDTO $dto, ReflectionClass $reflectionClass): array
    {
        $summary = [];
        $classAnnotationParameters = $this->readClassAnnotationParameters($reflectionClass);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            $parameters = [
                [
                    'path' => $propertyName
                ],
                $classAnnotationParameters
            ];

            $propertyAnnotation = $this->readPropertyAnnotation($reflectionProperty);

            if ($propertyAnnotation instanceof ParameterInterface) {
                if ($propertyAnnotation instanceof Parameter && $propertyAnnotation->disabled === true) {
                    // Do not inject parameter
                    continue;
                }

                $annotationParameters = $this->readPropertyAnnotationParameters(
                    $propertyAnnotation,
                    $reflectionProperty
                );

                $parameters[] = $annotationParameters;
            }

            if ($reflectionProperty->getValue($dto) !== null) {
                // If user define a default value it must not override with null
                $parameters[] = [
                    DTOParameters::PROPERTY_NULLABLE => false
                ];
            }

            // This code (array_replace(...$parameters)) is working like waterfall
            // Every element overrides its previous element
            $summary[$propertyName] = $this->parameterOptionsResolver->resolve(array_replace(...$parameters));

            // If parameter type has resolver, resolve parameters with using it
            if ($propertyAnnotation && isset($this->optionsResolvers[$propertyAnnotation->type])) {
                $summary[$propertyName][DTOParameters::PROPERTY_OPTIONS] =
                    $this->optionsResolvers[$propertyAnnotation->type]->resolve(
                        $summary[$propertyName][DTOParameters::PROPERTY_OPTIONS]
                    )
                ;
            }
        }

        return $summary;
    }

    protected function readClassAnnotationParameters(ReflectionClass $class): array
    {
        /**
         * @var ParameterInterface|null $annotation
         */
        $annotation = $this->annotationReader->getClassAnnotation($class, ParameterInterface::class);

        if ($annotation === null) {
            return [];
        }

        // We're filtering the options, because the null
        // values are overriding the parent configurations
        return $this->filterOptions(
            [
                DTOParameters::PROPERTY_TYPE => $annotation->type,
                DTOParameters::PROPERTY_SCOPE => $annotation->scope,
                DTOParameters::PROPERTY_DISABLED => $annotation->disabled,
                DTOParameters::PROPERTY_UNDEFINEDABLE => $annotation->undefinedable,
            ]
        );
    }

    /**
     * @param ReflectionProperty $property
     * @return object|ParameterInterface|null
     */
    protected function readPropertyAnnotation(ReflectionProperty $property)
    {
        return $this->annotationReader->getPropertyAnnotation($property, ParameterInterface::class);
    }

    protected function readPropertyAnnotationParameters(ParameterInterface $annotation, ReflectionProperty $property): array
    {
        $properties = [
            DTOParameters::PROPERTY_SCOPE => $annotation->scope,
            DTOParameters::PROPERTY_PATH => $annotation->path ?? $property->getName(),
            DTOParameters::PROPERTY_TYPE => $annotation->type,
            DTOParameters::PROPERTY_DISABLED => $annotation->disabled,
            DTOParameters::PROPERTY_OPTIONS => $annotation->options,
            DTOParameters::PROPERTY_UNDEFINEDABLE => $annotation->undefinedable
        ];

        // We're filtering the options, because the null
        // values are overriding the parent configurations
        return $this->filterOptions($properties);
    }

    protected function isDefined(Request $request, string $scope, string $path)
    {
        return $request->{$scope}->has($path);
    }

    protected function getValue(Request $request, string $scope, string $path)
    {
        $value = $this->propertyAccessor->getValue($request->{$scope}->all(), $this->normalizePath($path, $scope));

        return $value;
    }

    protected function castValue($value, array $parameters)
    {
        $typeCast = $parameters[DTOParameters::PROPERTY_TYPE];

        // If we apply typecast into null int returns 0, string returns "", bool returns false
        // It can be crash the application, to prevent this kind of circumstances we're checking the value is null or not
        if ($value === null) {
            return $value;
        }

        // If any callable binded to this type
        if (isset($this->castCallbacks[$typeCast])) {
            return call_user_func($this->castCallbacks[$typeCast], $value, $parameters);
        }

        if (is_array($value)) {
            return $value;
        }

        // Apply type cast
        settype($value, $typeCast);

        return $value;
    }

    protected function normalizePath(string $path, string $scope): string
    {
        if ($path[0] !== '[') {
            $path = sprintf('[%s]', $path);
        }

        // Because symfony's header bag stores variables in multidimensional arrays
        if ($scope === 'headers') {
            $path = strtolower($path) . '[0]';
        }

        return $path;
    }

    protected function filterOptions(array $options): array
    {
        return array_filter(
            $options,
            static function ($value) {
                return $value !== null;
            }
        );
    }

    public function supports(ParamConverter $configuration)
    {
        if (null === $configuration->getClass()) {
            return false;
        }

        return is_subclass_of($configuration->getClass(), RequestDTO::class);
    }
}
