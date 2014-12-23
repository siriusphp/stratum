<?php
namespace Sirius\Stratum;

trait TestTrait {
    function baz() {
        return 'trait';
    }
}

interface TestInterface {
    function pam();
}

class TestableLayerableObjectBase implements TestInterface
{
    use TestTrait;
    
    function foo($string = 'bar', $repeat = 1)
    {
        return str_repeat($string, $repeat);
    }

    function bar()
    {
        return 'baz';
    }
    
    function pam() {
        return 'interface';
    }
}

class TestableLayerableObject extends TestableLayerableObjectBase implements LayerableInterface
{
    use LayerableTrait;

    function foo($string = 'bar', $repeat = 1)
    {
        return $this->executeLayeredMethod(__FUNCTION__, func_get_args());
    }
    
    function baz() {
        return $this->executeLayeredMethod(__FUNCTION__, func_get_args());
    }
    
    function pam() {
        return $this->executeLayeredMethod(__FUNCTION__, func_get_args());
    }
}

class LayerA extends Layer
{

    function foo($string = 'bar', $repeat = 1)
    {
        return 'A' . $this->callNext(__FUNCTION__, array(
            $string,
            $repeat + 1
        ));
    }
}

class LayerB extends Layer
{

    function foo($string = 'bar', $repeat = 1)
    {
        return 'B' . $this->callNext(__FUNCTION__, array(
            '***' . $string,
            $repeat
        ));
    }

    function bar()
    {
        return 'foo';
    }
}

class LayerForTrait extends Layer {
    function baz() {
        return 'layer added to ' . $this->nextLayer->baz();
    }
}

class LayerForInterface extends Layer {
    
    function pam() {
        return 'layer added to ' . $this->nextLayer->pam();
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

    function testNoLayers()
    {
        $testableObj = new TestableLayerableObject();
        
        $this->assertEquals('bar', $testableObj->foo());
        $this->assertEquals('baz', $testableObj->bar());
    }

    function testClassLayers()
    {
        // decorators have the same priority, the order they were added matters
        $this->manager->add('Sirius\Stratum\LayerB', 'Sirius\Stratum\TestableLayerableObject');
        $this->manager->add('Sirius\Stratum\LayerA', 'Sirius\Stratum\TestableLayerableObject');
        
        $testableObj = new TestableLayerableObject();
//        var_dump($this->manager->createLayerStack($testableObj));
        
        $this->assertEquals('AB***bar***bar', $testableObj->foo());
        $this->assertEquals('baz', $testableObj->bar());
    }

    function testLayerPassedAsObject()
    {
        $this->manager->add(new LayerA(), 'Sirius\Stratum\TestableLayerableObject');
        
        $testableObj = new TestableLayerableObject();
        
        $this->assertEquals('Abarbar', $testableObj->foo());
    }

    function testLayerPassedAsCallback()
    {
        $this->manager->add(array(
            $this,
            'createValidLayer'
        ), 'Sirius\Stratum\TestableLayerableObject');
        
        $testableObj = new TestableLayerableObject();
        
        $this->assertEquals('Abarbar', $testableObj->foo());
    }

    function testExceptionThrownForMissingLayerClass()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->manager->add('SomeNonexistantClass', 'Sirius\Stratum\TestableLayerableObject');
    }

    function testExceptionThrownForInvalidLayerClass()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->manager->add('\stdClass', 'Sirius\Stratum\TestableLayerableObject');
    }

    function testExceptionThrownForInvalidObjectLayer()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->manager->add(new \stdClass(), 'Sirius\Stratum\TestableLayerableObject');
    }

    function testExceptionThrownForInvalidLayerCallback()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->manager->add(array(
            $this,
            'createInvalidLayer'
        ), 'Sirius\Stratum\TestableLayerableObject');
    }

    function testLayersOrder()
    {
        $this->manager->add('Sirius\Stratum\LayerA', 'Sirius\Stratum\TestableLayerableObject', 2);
        $this->manager->add('Sirius\Stratum\LayerB', 'Sirius\Stratum\TestableLayerableObject', 1);
        
        $testableObj = new TestableLayerableObject();
        
        $this->assertEquals('BA***bar***bar', $testableObj->foo());
    }
    
    function testLayerAttachedByInterface() 
    {
        $this->manager->add('Sirius\Stratum\LayerForInterface', 'implements:Sirius\Stratum\TestInterface');
        
        $testableObj = new TestableLayerableObject();
        #var_dump($this->manager->createLayerStack($testableObj));
        
        $this->assertEquals('layer added to interface', $testableObj->pam());
    }

    function testLayerAttachedByTrait() 
    {
        $this->manager->add('Sirius\Stratum\LayerForTrait', 'uses:Sirius\Stratum\TestTrait');
        
        $testableObj = new TestableLayerableObject();
        #var_dump($this->manager->createLayerStack($testableObj));
        
        $this->assertEquals('layer added to trait', $testableObj->baz());
    }

    function createInvalidLayer()
    {
        return 5;
    }

    function createValidLayer()
    {
        return new LayerA();
    }
}