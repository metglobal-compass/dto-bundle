<?php

namespace Gts\ApiBundle\Tests\Functional\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Metglobal\DTOBundle\DTOParamConverter;
use Metglobal\DTOBundle\Tests\Fixtures\CallableMethodDefinedClass;
use Metglobal\DTOBundle\Tests\Fixtures\ClassPropertyDefinedClass;
use Metglobal\DTOBundle\Tests\Fixtures\GroupDefinedClass;
use Metglobal\DTOBundle\Tests\Fixtures\NotSupportedClass;
use Metglobal\DTOBundle\Tests\Fixtures\PostSetEventDefinedClass;
use Metglobal\DTOBundle\Tests\Fixtures\PreSetEventDefinedClass;
use Metglobal\DTOBundle\Tests\Fixtures\PropertyDefinedClass;
use Metglobal\DTOBundle\Tests\Fixtures\SimpleClass;
use Metglobal\DTOBundle\Undefined;
use PHPUnit\Framework\MockObject\MockObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DTOParamConverterTest extends TestCase
{
    const VARIABLE_NAME = 'var';

    /** @var \Metglobal\DTOBundle\DTOParamConverter */
    protected $converter;

    public function setUp()
    {
        parent::setUp();

        $this->converter = new DTOParamConverter(new PropertyAccessor(), new AnnotationReader());
    }

    /**
     * @param null $class
     * @param null $name
     * @return MockObject|ParamConverter
     */
    public function createConfiguration($class = null, $name = null)
    {
        $config = $this
            ->getMockBuilder(ParamConverter::class)
            ->setMethods(['getClass', 'getAliasName', 'getOptions', 'getName', 'allowArray', 'isOptional'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        if (null !== $name) {
            $config
                ->expects($this->any())
                ->method('getName')
                ->willReturn($name)
            ;
        }

        if (null !== $class) {
            $config
                ->expects($this->any())
                ->method('getClass')
                ->willReturn($class)
            ;
        }

        return $config;
    }

    public function testSupports()
    {
        $this->assertTrue($this->converter->supports($this->createConfiguration(SimpleClass::class)));
        $this->assertFalse($this->converter->supports($this->createConfiguration(NotSupportedClass::class)));
    }

    /**
     * Simple convert process
     */
    public function testSimpleParameter()
    {
        $request = new Request([], ['testProperty' => 'test']);
        $configuration = $this->createConfiguration(SimpleClass::class, self::VARIABLE_NAME);
        $this->converter->apply($request, $configuration);

        /**
         * @var SimpleClass|null $target
         */
        $target = $request->attributes->get(self::VARIABLE_NAME);
        $this->assertInstanceOf(SimpleClass::class, $target);
        $this->assertSame($target->testProperty, 'test');
    }

    public function testCallableMethod()
    {
        $request = new Request();
        $configuration = $this->createConfiguration(CallableMethodDefinedClass::class, self::VARIABLE_NAME);
        $this->converter->apply($request, $configuration);

        /** @var CallableMethodDefinedClass|null $target */
        $target = $request->attributes->get(self::VARIABLE_NAME);
        // Call the callback method
        $target->call('givenValue', 'givenValue2', 'anotherValue');

        $this->assertInstanceOf(CallableMethodDefinedClass::class, $target);
        $this->assertSame($target->testProperty, 'givenValue');
    }

    public function testPreSetEventDefinedClass()
    {
        $request = new Request();
        $configuration = $this->createConfiguration(
            PreSetEventDefinedClass::class,
            self::VARIABLE_NAME
        );

        $this->converter->apply($request, $configuration);
        /** @var PreSetEventDefinedClass $target */
        $target = $request->attributes->get(self::VARIABLE_NAME);

        $this->assertInstanceOf(PreSetEventDefinedClass::class, $target);
        // We set this value in fixture class
        $this->assertSame($target->testProperty, 'testValue');
    }

    public function testPostSetEventDefinedClass()
    {
        $request = new Request([], ['testProperty' => 'test']);
        $configuration = $this->createConfiguration(
            PostSetEventDefinedClass::class,
            self::VARIABLE_NAME
        );

        $this->converter->apply($request, $configuration);
        /** @var PostSetEventDefinedClass $target */
        $target = $request->attributes->get(self::VARIABLE_NAME);

        $this->assertInstanceOf(PostSetEventDefinedClass::class, $target);
        // We sent it text, but we transformed this value into an array
        $this->assertSame($target->testProperty, ['test']);
    }

    /**
     * Simple convert process with missing value
     */
    public function testSimpleParameterWithMissingValue()
    {
        $request = new Request();
        $configuration = $this->createConfiguration(SimpleClass::class, self::VARIABLE_NAME);
        $this->converter->apply($request, $configuration);

        /** @var SimpleClass $target */
        $target = $request->attributes->get(self::VARIABLE_NAME);
        $this->assertInstanceOf(SimpleClass::class, $target);

        /**
         * Because in default properties are nullable
         * @see \Metglobal\DTOBundle\DTOParamConverter::PROPERTY_NULLABLE
         **/
        $this->assertNull($target->testProperty);
    }

    /**
     * We're just testing the property override process
     * Other things are tested above
     */
    public function testClassScopeDefinition()
    {
        $request = new Request(
            ['testScopeProperty' => 'wrongValue', 'testAnotherDefinitionProperty' => 1 ],
            ['testScopeProperty' => 'wrongValue2'],
            ['testScopeProperty' => 'trueValue']
        );

        $configuration = $this->createConfiguration(ClassPropertyDefinedClass::class, self::VARIABLE_NAME);
        $this->converter->apply($request, $configuration);

        /**
         * @var ClassPropertyDefinedClass|null $target
         */
        $target = $request->attributes->get(self::VARIABLE_NAME);
        $this->assertInstanceOf(ClassPropertyDefinedClass::class, $target);
        $this->assertSame($target->testScopeProperty, 'trueValue');
        $this->assertSame($target->testAnotherDefinitionProperty, 1);
    }

    private function assertDefinition(Request $request, string $propertyName, $actualValue)
    {
        $configuration = $this->createConfiguration(PropertyDefinedClass::class, self::VARIABLE_NAME);
        $this->converter->apply($request, $configuration);

        /**
         * @var PropertyDefinedClass|null $target
         */
        $target = $request->attributes->get(self::VARIABLE_NAME);
        $this->assertInstanceOf(PropertyDefinedClass::class, $target);

        if (is_object($actualValue)) {
            $this->assertInstanceOf(get_class($target->$propertyName), new Undefined);

            return;
        }

        $this->assertSame($target->$propertyName, $actualValue);
    }

    /**
     * Same with below pointed test
     *
     * @see \Metglobal\Compass\DTOTest::testSimpleParameter
     */
    public function testEmptyDefinition()
    {
        $request = new Request([], ['testProperty' => 'test']);
        $this->assertDefinition($request, 'testProperty', 'test');
    }

    /**
     * We're testing scope parameter
     */
    public function testScopeDefinition()
    {
        $request = new Request([], ['testScopeProperty' => 'wrongValue'], ['testScopeProperty' => 'trueValue']);
        $this->assertDefinition($request, 'testScopeProperty', 'trueValue');
    }

    /**
     * We're testing default value definition
     * It must not override default parameter with null
     */
    public function testNotNullableDefinition()
    {
        $request = new Request();
        $this->assertDefinition($request, 'testNotNullableProperty', 'defaultValue');
    }

    /**
     * We're testing default value definition
     * without any parameter here
     */
    public function testDefaultValueDefinition()
    {
        $request = new Request();
        $this->assertDefinition($request, 'testDefaultValueProperty', 'defaultValueWithoutAnnotation');
    }

    /**
     * We're testing path parameter
     */
    public function testPathDefinition()
    {
        $request = new Request([], ['examplePath' => 'testValue']);
        $this->assertDefinition($request, 'testPathProperty', 'testValue');
    }

    /**
     * We're testing type parameter
     */
    public function testTypeDefinition()
    {
        $request = new Request([], ['testTypeProperty' => '1']);
        $this->assertDefinition($request, 'testTypeProperty', 1);
    }

    public function testMixedTypeDefinition()
    {
        $request = new Request([], ['testMixedPath' => 1 ]);
        $this->assertDefinition($request, 'testMixedPath', 1);
    }

    public function floatDataProvider(): array
    {
        return [
            [
                5.30,
                5.30
            ],
            [
                '5.30',
                5.30
            ]
        ];
    }

    /**
     * @dataProvider floatDataProvider
     *
     * @param array $data
     * @param bool $sameWith
     */
    public function testFloatTypeDefinition($data, $sameWith)
    {
        $request = new Request([], ['testFloatProperty' => $data ]);
        $this->assertDefinition($request, 'testFloatProperty', $sameWith);
    }

    public function booleanDataProvider(): array
    {
        return [
            [
                true, //Equals to below definition
                true
            ],
            [
                'true',
                true
            ],
            [
                '1',
                true
            ],
            [
                false,
                false
            ],
            [
                'false',
                false
            ],
            [
                '0',
                false
            ],
            [
                null,
                null
            ]
        ];
    }

    /**
     * @dataProvider booleanDataProvider
     *
     * @param array $data
     * @param bool $sameWith
     */
    public function testBooleanTypeDefinition($data, $sameWith)
    {
        $request = new Request([], ['testBooleanProperty' => $data ]);
        $this->assertDefinition($request, 'testBooleanProperty', $sameWith);
    }

    /**
     * @dataProvider booleanDataProvider
     *
     * @param array $data
     * @param bool $sameWith
     */
    public function testBoolTypeDefinition($data, $sameWith)
    {
        $request = new Request([], ['testBoolProperty' => $data ]);
        $this->assertDefinition($request, 'testBoolProperty', $sameWith);
    }

    public function booleanWithDefaultValueDataProvider(): array
    {
        return [
            [
                true, //Equals to below definition
                true
            ],
            [
                'true',
                true
            ],
            [
                '1',
                true
            ],
            [
                false,
                false
            ],
            [
                'false',
                false
            ],
            [
                '0',
                false
            ],
            [
                null,
                true
            ]
        ];
    }

    /**
     * @dataProvider booleanWithDefaultValueDataProvider
     *
     * @param array $data
     * @param bool $sameWith
     */
    public function testBooleanTypeWithDefaultDefinition($data, bool $sameWith)
    {
        $request = new Request([], ['testBooleanWithDefaultProperty' => $data ]);
        $this->assertDefinition($request, 'testBooleanWithDefaultProperty', $sameWith);
    }

    public function testDisabledDefinition()
    {
        $request = new Request([], ['testDisabledProperty' => 5 ]);
        $this->assertDefinition($request, 'testDisabledProperty', null);
    }

    public function testHeaderScopeDefinition()
    {
        $request = new Request();
        $request->headers->set('testHeaderProperty', '5');
        $this->assertDefinition($request, 'testHeaderProperty', '5');
    }

    public function testUndefinedableUndefinedValueDefinition()
    {
        $request = new Request();
        $this->assertDefinition($request, 'testUndefinedableProperty', new Undefined);
    }

    public function testUndefinedableDefinedValueDefinition()
    {
        $request = new Request([], ['testUndefinedableProperty' => '1']);
        $this->assertDefinition($request, 'testUndefinedableProperty', '1');
    }

    private function assertDateDefinition(Request $request, string $propertyName, $actualValue)
    {
        $configuration = $this->createConfiguration(PropertyDefinedClass::class, self::VARIABLE_NAME);
        $this->converter->apply($request, $configuration);

        /** @var PropertyDefinedClass|null $target */
        $target = $request->attributes->get(self::VARIABLE_NAME);
        $this->assertInstanceOf(PropertyDefinedClass::class, $target);

        /** @var \DateTime|null $computedDate */
        $computedDate = $target->$propertyName;

        if ($actualValue === null) {
            $this->assertNull($target->$propertyName);
        } elseif ($actualValue instanceof Undefined) {
            $this->assertInstanceOf(get_class($target->$propertyName), new Undefined);
        } else {
            $this->assertInstanceOf(\DateTime::class, $computedDate);
            $this->assertSame($computedDate->getTimestamp(), $actualValue->getTimestamp());
            $this->assertSame($computedDate->getTimezone()->getName(), $actualValue->getTimezone()->getName());
        }
    }

    public function testUndefinedDateDefinition()
    {
        $request = new Request();
        $this->assertDateDefinition(
            $request,
            'testDateProperty',
            new Undefined()
        );
    }

    public function testNullDefinedDateDefinition()
    {
        $request = new Request([], ['testDateProperty' => null]);
        $this->assertDateDefinition(
            $request,
            'testDateProperty',
            null
        );
    }

    public function testSimpleDateDefinition()
    {
        $date = new \DateTime();
        $request = new Request([], ['testDateProperty' => $date->format('Y-m-d H:i:s')]);
        $this->assertDateDefinition(
            $request,
            'testDateProperty',
            $date
        );
    }

    public function testFormatDateDefinition()
    {
        $date = new \DateTime();
        $request = new Request([], ['testDateWithFormatProperty' => $date->format('m.d.Y')]);
        $this->assertDateDefinition(
            $request,
            'testDateWithFormatProperty',
            $date
        );
    }

    public function testTimeZoneDateDefinition()
    {
        $date = new \DateTime('now', new \DateTimeZone('Europe/London'));
        $request = new Request([], ['testDateWithTimeZoneProperty' => $date->format('Y-m-d H:i:s')]);
        $this->assertDateDefinition(
            $request,
            'testDateWithTimeZoneProperty',
            $date
        );
    }

    private function assertGroupDefinition(Request $request, array $expectedValues)
    {
        $configuration = $this->createConfiguration(GroupDefinedClass::class, self::VARIABLE_NAME);
        $this->converter->apply($request, $configuration);

        /** @var GroupDefinedClass|null $target */
        $target = $request->attributes->get(self::VARIABLE_NAME);
        $this->assertInstanceOf(GroupDefinedClass::class, $target);

        foreach ($expectedValues as $index => $value) {
            $this->assertObjectHasAttribute($index, $target);
            $this->assertTrue(is_array($target->{$index}));

            $this->assertEmpty(
                array_diff_assoc($target->{$index}, $value),
                sprintf(
                    'Group value is not valid. Given: "%s", expected: "%s"',
                    implode(',', $target->{$index}),
                    implode(',', $value)
                )
            );
        }
    }

    public function groupDataProvider(): array
    {
        return [
            [ // Case 1
                [ // $data
                    [ // $request->query
                        'undefinedableProperty' => 'undefinedableProperty',
                        'nullableProperty' => null,
                    ],
                    [ // $request->request
                        'simpleProperty' => 'test',
                        'annotationDefinedProperty' => 'testAnnotation',
                        'disabledGroupProperty' => 'testDisabled',
                        // Injecting group property is not a valid operation
                        'groupTarget' => 'notValidParameter',
                        // Injecting disabled property into group is not a valid operation
                        'parameterDisabledProperty' => 'testDisabledParameter'
                    ]
                ],
                [ // $sameWith
                    'groupTarget' => [
                        'simpleProperty' => 'test',
                        'undefinedableProperty' => 'undefinedableProperty',
                        'nullableProperty' => null,
                        'parameterDisabledProperty' => null
                    ],
                    'nextTarget' => [
                        'annotationDefinedProperty' => 'testAnnotation'
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider groupDataProvider
     */
    public function testGroupDefinition(array $data, array $sameWith)
    {
        $request = new Request(...$data);

        $this->assertGroupDefinition($request, $sameWith);
    }
}
