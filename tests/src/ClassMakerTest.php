<?php
namespace Sirius\Stratum;

class TestLayerableClass {
    
}

class TestLayerableClassBase 
{
    /**
     * @Stratum\Layerable
     */
    function doSomething($arg1, $arg2 = 3, $arg3 = false)
    {
        return 'something';
    }

    /**
     * @Stratum\Notlayerable
     */
    function doSomethingElse($arg1, $arg2 = 3, $arg3 = false) 
    {
        return 'something else';
    }
}

class LayerableClassMakerTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->classMaker = new ClassMaker();
    }
    
    function testCreateClassContent()
    {
        $expected = <<<TXT
<?php
namespace Sirius\Stratum;

class TestLayerableClass extends TestLayerableClassBase
{
    use Sirius\Stratum\LayerableTrait;

    function doSomething(\$arg1, \$arg2 = 3, \$arg3 = false)
    {
        if (\$this->topLayer) {
            return \$this->topLayer->doSomething(\$arg1, \$arg2, \$arg3);
        }
        return parent::doSomething(\$arg1, \$arg2, \$arg3);
    }

}        
TXT;
        $this->assertEquals(trim($expected), trim($this->classMaker->makeForClass('\Sirius\Stratum\TestLayerableClassBase', 'class')));
    }
    

    function testCreateLayerContent()
    {
        $expected = <<<TXT
<?php
namespace Sirius\Stratum;

class TestLayerableClassLayer extends Sirius\Stratum\Layer
{

    function doSomething(\$arg1, \$arg2 = 3, \$arg3 = false)
    {
        return \$this->nextLayer->doSomething(\$arg1, \$arg2, \$arg3);
    }

}        
TXT;
        $this->assertEquals(trim($expected), trim($this->classMaker->makeForClass('\Sirius\Stratum\TestLayerableClassBase', 'layer')));
    }
    

    function testCreateWrapperContent()
    {
        $expected = <<<TXT
<?php
namespace Sirius\Stratum;

class TestLayerableClassWrapper extends Sirius\Stratum\Layer
{

    function doSomething(\$arg1, \$arg2 = 3, \$arg3 = false)
    {
        return \$this->object->callParentMethod('doSomething', func_get_args());
    }

}        
TXT;
        $this->assertEquals(trim($expected), trim($this->classMaker->makeForClass('\Sirius\Stratum\TestLayerableClassBase', 'wrapper')));
    }
    

    function testExceptionThrownForNonExistantClass()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->classMaker->makeForClass('NonExistantClass', 'class');
    }

    function testExceptionThrownForClassesThatDontEndWithBase()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->classMaker->makeForClass('\Sirius\Stratum\TestLayerableClass', 'class');
    }
    
}