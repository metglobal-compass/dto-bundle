# Metglobal Compass DTO Bundle
This bundle is focused to inject request parameters into data transfer objects.

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require metglobal-compass/dto-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require metglobal-compass/dto-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Metglobal\DTOBundle\DTOBundle::class => ['all' => true],
];
```

### Step 3: Enable the param converter
```yaml
Metglobal\DTOBundle\DTOParamConverter:
    tags:
        - { name: request.param_converter, priority: -2, converter: dto_converter }
```

How to use
============
Define a controller method parameter with type hint. The type hint must be instance of `\Metglobal\Compass\Domain\DTO\Request`. If it is symfony will try to resolve the parameters with default configurations. Using the `\Metglobal\DTOBundle\DTOParamConverter`.

See: `\Metglobal\DTOBundle\DTOParamConverter::supports`.

Default parameter resolver parameters (see: `\Metglobal\DTOBundle\OptionsResolver\ParameterOptionsResolver`, `\Metglobal\DTOBundle\OptionsResolver\DateParameterOptionsResolver`):

    [ 'type' => 'string', 'scope' => 'request', 'disabled' => false, 'options' => [], 'undefined' => false ]

Property annotation example: 

    @Metglobal\Compass\Annotation\DTO\Parameter(
        type="string",
        scope="request",
        path="pathOfThisParameter",
        disabled=false,
        options={},
        undefined=false
    )


Available property annotation options
===========================
The converter will try to resolve all the things automatically with defaults but you can configure below parameters using the `Metglobal\Compass\Annotation\DTO\Parameter` annotation and also if you do not define this annotation to property it'll try to resolve the itself too.

type:
----
Variable's type. Available types are: 'string', 'boolean', 'bool', 'integer', 'int' and 'mixed'. Mixed type allows you to apply any type of value into property.

**Warning:** boolean type is exceptional see: `\Symfony\Component\HttpFoundation\ParameterBag::getBoolean`. 

scope:
-----
Variable's scope. Available scopes are: 'request', 'query', 'headers', 'attributes'

path:
----
Variable's path. It's default is property's name but you can customise the path of variable.
Example:
Url: `*.com?testPath=3`
```php
/**
 * @Parameter(scope="query", path="testPath")
 */
public $differentName;
```

It'll set 3 into $differentName.

**Warning:** This parameter required per property if you do not define, it'll try to resolve the parameter with its property name. It means in above example path will `differentName`.

disabled:
--------
Disable injection for the selected parameter.

options:
--------

    format:
    -------
    Available when you set type to date. You can configure input datetime's format with this property.
    
    timezone:
    --------
    Available when you set type to date. You can configure datetime's timezone with this property.

undefinedable:
--------------
Make property undefinedable. This property makes you able to find undefined properties.

Extra annotation tips
=====================
This annotation can be use at property or class. If you define this property to class it will effect all the classes properties.

    Default parameters overrides --> Class annotation parameters (If exists) overrides --> Property annotation parameters (If exists)

For every parameter it call the method that finds the final injection configs **per property**.
Example:

```php
<?php
namespace Metglobal\Compass\Request;

use Metglobal\DTOBundle\Annotation\Parameter;
use Metglobal\DTOBundle\Request;
use Metglobal\DTOBundle\Undefined;

/**
 * @Parameter(scope="query")
 */
class DummyRequest implements Request
{
    /**
    * @Parameter(type="int")
    *
    * @var int|null
    */
    public $foo;
  
    /**
    * @Parameter(type="int")
    *
    * @var int|null
    */
    public $bar;
}
```
In above class, the `$foo` will inject from `query` scope and the `$bar` will inject from query with
integer type cast.

Null/Undefined variables:
--------
Every defined property that has no default value is nullable and can be undefined.

**Warning:** If you define a default value to property, converter inject the default value instead of null.

Example:

```php
namespace Metglobal\Compass\Request;

use Metglobal\DTOBundle\Annotation\Parameter;
use Metglobal\DTOBundle\Request;
use Metglobal\DTOBundle\Undefined;

class DummyRequest implements Request
{
    /**
    * @Parameter(scope="query", undefined=true)
    *
    * @var string|null|Undefined
    */
    public $foo;
}
```
Results would be:

| ?foo= | ?foo=bar | ?baz=5 |
|---------------|------------------| ---------------- |
| null          | "bar"            | Undefined()      |

Life cycle events
=================

\Metglobal\DTOBundle\Annotation\PreSet:
---------------------------------------

Defining this annotation onto methods allows you to access properties **before** setting the request parameters into target class.

\Metglobal\DTOBundle\Annotation\PostSet:
---------------------------------------

Defining this annotation onto methods allows you to access properties **after** setting the request parameters into target class.

The \Metglobal\DTOBundle\CallableRequest interface
======================================================
You should inject all the simple parameters using above configurations with the `@Metglobal\Compass\Annotation\DTO\Parameter` annotation but if there is a complex logic that the annotation can not handle you can use this interface as callback method.

In `call()` method you can modify the object's properties using `...$args` variables.
**Tip 1:** Recommended way to hand basic processes is using the life cycle events. Only use this interface if you need to inject anything into target class. 
**Tip 2:** If you dont know what does `...$args` mean see the RFC: https://wiki.php.net/rfc/argument_unpacking.

Example usage:
--------------
### Controller:

```php
<?php
namespace Metglobal\Compass\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
...

class DummyController extends Controller
{
  /**
   * @Route("/dummy/{id}", requirements={"id" = "\d+"}, methods={"DELETE"}, name="dummy_route")
   */
   public function __invoke(FooService $fooService, BarService $barService, BazService $bazService, DummyRequest $request): JsonResponse
   {
       $request->call($fooService, $barService);

       return new JsonResponse($bazService->handle($request));
   }
}
```

### Request:

```php
<?php
namespace Metglobal\Compass\Request;

use Metglobal\DTOBundle\Annotation\Parameter;
use Metglobal\DTOBundle\Annotation\PostSet;
use Metglobal\DTOBundle\Annotation\PreSet;
use Metglobal\DTOBundle\CallableRequest;
use Metglobal\DTOBundle\Undefined;

/**
 * @Parameter(scope="query")
 */
class DummyRequest implements CallableRequest
{
  /**
   * @Parameter(type="int")
   * 
   * @var int|null
   */
   public $foo;

   public $bar;

   public $baz;

   /**
    * @var \DateTime|null
    * 
    * @Parameter(type="date", options={format="Y-m-d"}) 
    */
   public $fooBar;

   public function call(...$args)
   {
       [ $fooService, $barService ] = $args;

        $this->foo = $fooService->aMethod($barService->bMethod($this->foo));
   }

   /**
    * This method will run before setting the parameters 
    * 
    * @PreSet()
    */
   public function preSetXMethod(){
       $this->baz = 1;
   }

   /**
    * This method will run after setting the parameters
    * 
    * @PostSet()
    */
   public function postSetXMethod(){
       $this->baz = 5;
   }
}
```

Groups
=================

You can group injected properties using `@Metglobal\DTOBundle\Annotation\Group` annotation.

Example:

```php
<?php
namespace Metglobal\Compass\Request;

use Metglobal\DTOBundle\Annotation\Parameter;
use Metglobal\DTOBundle\CallableRequest;
use Metglobal\DTOBundle\Undefined;

/**
 * @Group("fooGroup")
 * @Parameter(scope="query")
 */
class DummyRequest implements CallableRequest
{
   /**
    * @Parameter(type="int", undefined=true)
    * @Group(target="barGroup")
    * 
    * @var int|null|Undefined
    */
   public $foo;

   /**
    * @Group(disabled=true)
    */
   public $bar;

   public $baz;
   
   /** @var array */
   public $fooGroup;
}
```

Query: `?foo=fooValue&bar=barValue&baz=bazValue&exampleGroup=bug&nextGroup=bug`

`$fooGroup` will be:

```php
[
    'baz' => 'bazValue',
];
```

`$barGroup` will be (defined dynamically):

```php
[
    'foo' => 'fooValue',
];
```

Available Group annotation options
===========================
You can use this annotation on classes (it will apply the group options on every property), or on property

target:
--------
Target group variable. Either the target is defined or not, the converter will put the variables into given target.

disabled:
--------
You can still use property injection but it disables grouping for property or whole class.

Important Notes:
----------------

- Group properties are not injectable
- Group annotation can be used on class or property
- You can modify groups with `@Metglobal\DTOBundle\Annotation\PostSet` event
- If you disable property injection (`@Metglobal\DTOBundle\Annotation\Parameter(disabled=true)`) it will also set the property into group. To disable set group's `disabled` option to true.

Contributing
============
If you're having problems, spot a bug, or have a feature suggestion, please log and issue on Github. If you'd like to have a crack yourself, fork the package and make a pull request. Please include tests for any added or changed functionality. If it's a bug, include a regression test.
