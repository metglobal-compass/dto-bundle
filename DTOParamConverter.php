<?php

namespace Metglobal\DTOBundle;

use Doctrine\Common\Annotations\Reader;
use Metglobal\DTOBundle\Annotation\DateParameter;
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
    const PROPERTY_OPTION_SCOPE = 'scope';
    const PROPERTY_OPTION_PATH = 'path';
    const PROPERTY_OPTION_TYPE = 'type';
    const PROPERTY_OPTION_NULLABLE = 'nullable';
    const PROPERTY_OPTION_DISABLED = 'disabled';

    const DEFAULT_OPTION_TYPE = 'string';
    const DEFAULT_OPTION_SCOPE = 'request';
    const DEFAULT_OPTION_NULLABLE = true;
    const DEFAULT_OPTION_DISABLED = false;

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var Reader */
    protected $annotationReader;

    /** @var ParameterOptionsResolver */
    protected $parameterOptionsResolver;

    /** @var DateParameterOptionsResolver */
    protected $dateParameterOptionsResolver;

    public function __construct(PropertyAccessorInterface $propertyAccessor, Reader $annotationReader)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->annotationReader = $annotationReader;
        $this->parameterOptionsResolver = new ParameterOptionsResolver();
        $this->dateParameterOptionsResolver = new DateParameterOptionsResolver();
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        try {
            $class = $configuration->getClass();
            $instance = new $class();
            $request->attributes->set($configuration->getName(), $instance);

            $reflectionClass = new ReflectionClass($instance);
            $this->callEvent($reflectionClass, $instance, PreSet::class);

            foreach ($this->getProperties($instance, $reflectionClass) as $parameter => $parameterOptions) {
                $value = $this->getValue(
                    $request,
                    $parameterOptions[self::PROPERTY_OPTION_SCOPE],
                    $parameterOptions[self::PROPERTY_OPTION_PATH]
                );

                if ($parameterOptions[self::PROPERTY_OPTION_TYPE] === 'date') {
                    $value = $this->castToDate(
                        $value,
                        $parameterOptions[DateParameterOptionsResolver::PROPERTY_OPTION_FORMAT],
                        $parameterOptions[DateParameterOptionsResolver::PROPERTY_OPTION_TIMEZONE]
                    );
                } else {
                    $value = $this->castValue($value, $parameterOptions[self::PROPERTY_OPTION_TYPE]);
                }

                if ($value !== null || ($value === null && $parameterOptions[self::PROPERTY_OPTION_NULLABLE] === true)) {
                    $this->propertyAccessor->setValue($instance, $parameter, $value);
                }
            }

            $this->callEvent($reflectionClass, $instance, PostSet::class);
        } catch (Throwable $e) {
            throw new DTOException('An error occurred while setting parameters into DTO.', $e);
        }

        return true;
    }

    protected function callEvent(ReflectionClass $reflectionClass, RequestDTO $instance, string $eventClass)
    {
        foreach ($reflectionClass->getMethods( \ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $annotation = $this->annotationReader->getMethodAnnotation($reflectionMethod, $eventClass);

            if ($annotation === null) {
                continue;
            }

            $reflectionMethod->invoke($instance);
        }
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
                    self::PROPERTY_OPTION_NULLABLE => false
                ];
            }

            // This code (array_replace(...$parameters)) is working like waterfall
            // Every element overrides its previous element
            if ($propertyAnnotation instanceof DateParameter) {
                $summary[$propertyName] = $this->dateParameterOptionsResolver->resolve(array_replace(...$parameters));
            } else {
                $summary[$propertyName] = $this->parameterOptionsResolver->resolve(array_replace(...$parameters));
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
                self::PROPERTY_OPTION_TYPE => $annotation->type,
                self::PROPERTY_OPTION_SCOPE => $annotation->scope,
                self::PROPERTY_OPTION_DISABLED => $annotation->disabled,
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
        $options = [
            self::PROPERTY_OPTION_SCOPE => $annotation->scope,
            self::PROPERTY_OPTION_PATH => $annotation->path ?? $property->getName(),
            self::PROPERTY_OPTION_TYPE => $annotation->type,
            self::PROPERTY_OPTION_DISABLED => $annotation->disabled
        ];

        if ($options[self::PROPERTY_OPTION_TYPE] === 'date') {
            $options[DateParameterOptionsResolver::PROPERTY_OPTION_FORMAT] = $annotation->format;
            $options[DateParameterOptionsResolver::PROPERTY_OPTION_TIMEZONE] = $annotation->timezone;
        }

        // We're filtering the options, because the null
        // values are overriding the parent configurations
        return $this->filterOptions($options);
    }

    protected function getValue(Request $request, string $scope, string $path)
    {
        $value = $this->propertyAccessor->getValue($request->{$scope}->all(), $this->normalizePath($path, $scope));

        return $value;
    }

    protected function castValue($value, string $typeCast)
    {
        // Boolean type is exception
        // They are not nullable fields
        // Because of the definition of \Symfony\Component\HttpFoundation\ParameterBag::getBoolean
        if ($typeCast === 'boolean') {
            /**
             * We must return null if user did not send value
             * instead of false
             */
            if ($value === null) {
                return null;
            }

            /**
             * @See: https://www.w3schools.com/php/filter_validate_boolean.asp
             */
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        // If we apply typecast into null int returns 0, string returns "", bool returns false
        // It can be crash the application, to prevent this kind of circumstances we're checking the value is null or not
        if ($value !== null && is_array($value) === false) {
            // Apply type cast
            settype($value, $typeCast);
        }

        return $value;
    }

    protected function castToDate($date, string $format, $timezone)
    {
        if ($date === null) {
            return null;
        }

        if ($timezone) {
            return \DateTime::createFromFormat($format, $date, new \DateTimeZone($timezone));
        }

        return \DateTime::createFromFormat($format, $date);
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
