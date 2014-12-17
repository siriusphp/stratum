<?php
namespace Sirius\Decorators;

class TestableDecoratableObjectBase
{

    function foo($repeat = 1)
    {
        return str_repeat('bar', $repeat);
    }

    function bar()
    {
        return 'baz';
    }
}

class TestableDecoratableObject extends TestableDecoratableObjectBase implements DecoratableInterface
{
    use DecoratableTrait;

    function foo($repeat = 1)
    {
        return $this->executeDecoratedMethod(__FUNCTION__, func_get_args());
    }
}

class DecoratorA extends Decorator
{

    function foo($repeat = 1)
    {
        return $this->callNext(__FUNCTION__, array(
            $repeat + 1
        ));
    }
}

class DecoratorB extends Decorator
{

    function foo($repeat = 1)
    {
        return '***' . $this->callNext(__FUNCTION__, func_get_args());
    }

    function bar()
    {
        return 'foo';
    }
}

class ManagerTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var Manager
     */
    protected $manager;

    function setUp()
    {
        $this->manager = Manager::resetInstance();
    }

    function testNoDecorators()
    {
        $testableObj = new TestableDecoratableObject();
        
        $this->assertEquals('bar', $testableObj->foo());
        $this->assertEquals('baz', $testableObj->bar());
    }

    function testClassDecorators()
    {
        // decorators have the same priority, the order they were added matters
        $this->manager->add('Sirius\Decorators\DecoratorA', 'Sirius\Decorators\TestableDecoratableObject');
        $this->manager->add('Sirius\Decorators\DecoratorB', 'Sirius\Decorators\TestableDecoratableObject');
        
        $testableObj = new TestableDecoratableObject();
        
        $this->assertEquals('***barbar', $testableObj->foo());
        $this->assertEquals('baz', $testableObj->bar());
    }
    
    function testDecoratorPassedAsObject() {
        $this->manager->add(new DecoratorA, 'Sirius\Decorators\TestableDecoratableObject');
        
        $testableObj = new TestableDecoratableObject();
        
        $this->assertEquals('barbar', $testableObj->foo());
    }

    function testDecoratorPassedAsCallback() {
        $this->manager->add(array($this, 'createValidDecorator'), 'Sirius\Decorators\TestableDecoratableObject');
        
        $testableObj = new TestableDecoratableObject();
        
        $this->assertEquals('barbar', $testableObj->foo());
    }

    function testExceptionThrownForMissingDecoratorClass()
    {
    	$this->setExpectedException('InvalidArgumentException');
    	$this->manager->add('SomeNonexistantClass', 'Sirius\Decorators\TestableDecoratableObject');
    }

    function testExceptionThrownForInvalidDecoratorClass()
    {
    	$this->setExpectedException('InvalidArgumentException');
    	$this->manager->add('\stdClass', 'Sirius\Decorators\TestableDecoratableObject');
    }

    function testExceptionThrownForInvalidObjectDecorator()
    {
    	$this->setExpectedException('InvalidArgumentException');
    	$this->manager->add(new \stdClass(), 'Sirius\Decorators\TestableDecoratableObject');
    }


    function testExceptionThrownForInvalidDecoratorCallback()
    {
    	$this->setExpectedException('InvalidArgumentException');
    	$this->manager->add(array($this, 'createInvalidDecorator') , 'Sirius\Decorators\TestableDecoratableObject');
    }
    
    function createInvalidDecorator() {
        return 5;
    }
    
    function createValidDecorator() {
        return new DecoratorA();
    }
}