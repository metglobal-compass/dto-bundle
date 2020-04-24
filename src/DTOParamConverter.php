<?php

namespace Metglobal\Compass\DTO;

use Doctrine\Common\Annotations\Reader;
use Metglobal\Compass\DTO\Annotation\Parameter;
use Metglobal\Compass\DTO\Request AS RequestDTO;
use Metglobal\Compass\DTO\Exception\DTOException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Throwable;

/**
 * Class DTOParamConverter
 * @package Metglobal\Compass\Service
 */
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

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(PropertyAccessorInterface $propertyAccessor, Reader $annotationReader)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->annotationReader = $annotationReader;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        try {
            $class = $configuration->getClass();
            $instance = new $class;
            $request->attributes->set($configuration->getName(), $instance);

            foreach ($this->getProperties($instance) AS $parameter => $parameterOptions) {
                $value = $this->getValue($request,
                    $parameterOptions[self::PROPERTY_OPTION_SCOPE],
                    $parameterOptions[self::PROPERTY_OPTION_PATH],
                    $parameterOptions[self::PROPERTY_OPTION_TYPE]
                );

                if ($value !== null || ($value === null && $parameterOptions[self::PROPERTY_OPTION_NULLABLE] === true)) {
                    $this->propertyAccessor->setValue($instance, $parameter, $value);
                }
            }
        } catch (Throwable $e) {
            throw new DTOException('An error occurred while setting parameters into DTO.', $e);
        }

        return true;
    }

    private function getParameterOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            self::PROPERTY_OPTION_TYPE => self::DEFAULT_OPTION_TYPE,
            self::PROPERTY_OPTION_SCOPE => self::DEFAULT_OPTION_SCOPE,
            self::PROPERTY_OPTION_NULLABLE => self::DEFAULT_OPTION_NULLABLE,
            self::PROPERTY_OPTION_DISABLED => self::DEFAULT_OPTION_DISABLED,
        ]);

        $resolver->setAllowedTypes(self::PROPERTY_OPTION_NULLABLE, [ 'boolean', 'null' ]);
        $resolver->setAllowedTypes(self::PROPERTY_OPTION_DISABLED, [ 'boolean', 'null' ]);
        $resolver->setAllowedValues(self::PROPERTY_OPTION_TYPE, [ 'string', 'boolean', 'integer', 'int', null ]);
        $resolver->setAllowedValues(self::PROPERTY_OPTION_SCOPE, [ 'request', 'query', 'headers', 'attributes', null ]);
        $resolver->setRequired([ self::PROPERTY_OPTION_PATH ]);

        return $resolver;
    }

    /**
     * @param RequestDTO $dto
     * @return array
     * @throws ReflectionException
     */
    private function getProperties(RequestDTO $dto): array
    {
        $reflectionClass = new ReflectionClass($dto);
        $parameterOptionsResolver = $this->getParameterOptionsResolver();

        $summary = [];
        $classAnnotationParameters = $this->readClassAnnotationParameters($reflectionClass);
        foreach ($reflectionClass->getProperties() AS $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            $parameters = [
                [
                    'path' => $propertyName
                ],
                $classAnnotationParameters
            ];

            $propertyAnnotation = $this->readPropertyAnnotation($reflectionProperty);

            if ($propertyAnnotation instanceof Parameter) {
                if ($propertyAnnotation->disabled === true) {
                    // Do not inject parameter
                    continue;
                }

                $annotationParameters = $this->readPropertyAnnotationParameters($propertyAnnotation, $reflectionProperty);

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
            $summary[$propertyName] = $parameterOptionsResolver->resolve(array_replace(...$parameters));
        }

        return $summary;
    }

    private function readClassAnnotationParameters(ReflectionClass $class): array
    {
        /**
         * @var Parameter|null $annotation
         */
        $annotation = $this->annotationReader->getClassAnnotation($class, Parameter::class);

        if ($annotation === null) {
            return [];
        }

        // We're filtering the options, because the null
        // values are overriding the parent configurations
        return $this->filterOptions([
            self::PROPERTY_OPTION_TYPE => $annotation->type,
            self::PROPERTY_OPTION_SCOPE => $annotation->scope,
            self::PROPERTY_OPTION_DISABLED => $annotation->disabled,
        ]);
    }

    /**
     * @param ReflectionProperty $property
     * @return object|Parameter|null
     */
    private function readPropertyAnnotation(ReflectionProperty $property)
    {
        return $this->annotationReader->getPropertyAnnotation($property, Parameter::class);
    }

    private function readPropertyAnnotationParameters(Parameter $annotation, ReflectionProperty $property): array
    {
        // We're filtering the options, because the null
        // values are overriding the parent configurations
        return $this->filterOptions([
            self::PROPERTY_OPTION_TYPE => $annotation->type,
            self::PROPERTY_OPTION_SCOPE => $annotation->scope,
            self::PROPERTY_OPTION_DISABLED => $annotation->disabled,
            self::PROPERTY_OPTION_PATH => $annotation->path ?? $property->getName(),
        ]);
    }

    private function getValue(Request $request, string $scope, string $path, string $typeCast)
    {
        $value = $this->propertyAccessor->getValue($request->{$scope}->all(), $this->normalizePath($path, $scope));

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

    private function normalizePath(string $path, string $scope): string
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

    private function filterOptions(array $options): array
    {
        return array_filter($options, static function ($value) {
            return $value !== null;
        });
    }

    public function supports(ParamConverter $configuration)
    {
        if (null === $configuration->getClass()) {
            return false;
        }

        return is_subclass_of($configuration->getClass(), RequestDTO::class);
    }
}
