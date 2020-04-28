<?php

namespace Gts\ApiBundle\Tests\Functional\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Metglobal\DTOBundle\DTOParamConverter;
use Metglobal\DTOBundle\Tests\Fixtures\CallableMethodDefinedClass;
use Metglobal\DTOBundle\Tests\Fixtures\ClassPropertyDefinedClass;
use Metglobal\DTOBundle\Tests\Fixtures\NotSupportedClass;
use Metglobal\DTOBundle\Tests\Fixtures\PropertyDefinedClass;
use Metglobal\DTOBundle\Tests\Fixtures\SimpleClass;
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
        $request = new Request([], [ 'testProperty' => 'test' ]);
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

        /**
         * @var CallableMethodDefinedClass|null $target
         */
        $target = $request->attributes->get(self::VARIABLE_NAME);
        // Call the callback method
        $target->call('givenValue', 'givenValue2', 'anotherValue');

        $this->assertInstanceOf(CallableMethodDefinedClass::class, $target);
        $this->assertSame($target->testProperty, 'givenValue');
    }

    /**
     * Simple convert process with missing value
     */
    public function testSimpleParameterWithMissingValue()
    {
        $request = new Request();
        $configuration = $this->createConfiguration(SimpleClass::class, self::VARIABLE_NAME);
        $this->converter->apply($request, $configuration);

        /**
         * @var SimpleClass|null $target
         */
        $target = $request->attributes->get(self::VARIABLE_NAME);
        $this->assertInstanceOf(SimpleClass::class, $target);
        /**
         * Because in default properties are nullable
         * @see \Gts\ApiBundle\DTO\DTOParamConverter::PROPERTY_OPTION_NULLABLE
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
            [ 'testScopeProperty' => 'wrongValue', 'testAnotherDefinitionProperty' => 1 ],
            [ 'testScopeProperty' => 'wrongValue2' ],
            [ 'testScopeProperty' => 'trueValue' ]
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
        $this->assertSame($target->$propertyName, $actualValue);
    }

    /**
     * Same with below pointed test
     *
     * @see \Metglobal\Compass\DTOTest::testSimpleParameter
     */
    public function testEmptyDefinition()
    {
        $request = new Request([], [ 'testProperty' => 'test' ]);
        $this->assertDefinition($request, 'testProperty', 'test');
    }

    /**
     * We're testing scope parameter
     */
    public function testScopeDefinition()
    {
        $request = new Request([], [ 'testScopeProperty' => 'wrongValue' ], [ 'testScopeProperty' => 'trueValue' ]);
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
        $request = new Request([], [ 'examplePath' => 'testValue' ]);
        $this->assertDefinition($request, 'testPathProperty', 'testValue');
    }

    /**
     * We're testing path parameter with expression
     */
    public function testPathExpressionDefinition()
    {
        $request = new Request([], [ 'exampleExpressionPath' => [ 'exampleChild' => 'testValue' ] ]);
        $this->assertDefinition($request, 'testPathWithExpressionProperty', 'testValue');
    }

    /**
     * We're testing type parameter
     */
    public function testTypeDefinition()
    {
        $request = new Request([], [ 'testTypeProperty' => '1' ]);
        $this->assertDefinition($request, 'testTypeProperty', 1);
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
        $request = new Request([], [ 'testBooleanProperty' => $data ]);
        $this->assertDefinition($request, 'testBooleanProperty', $sameWith);
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
        $request = new Request([], [ 'testBooleanWithDefaultProperty' => $data ]);
        $this->assertDefinition($request, 'testBooleanWithDefaultProperty', $sameWith);
    }

    public function testDisabledDefinition()
    {
        $request = new Request([], [ 'testDisabledProperty' => 5 ]);
        $this->assertDefinition($request, 'testDisabledProperty', null);
    }

    public function testHeaderScopeDefinition()
    {
        $request = new Request();
        $request->headers->set('testHeaderProperty', '5');
        $this->assertDefinition($request, 'testHeaderProperty', '5');
    }
}
