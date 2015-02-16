<?php

include('../autoload.php');

/**
 * Regular decorators
 */

class SampleBase {
    function add($a, $b) {
        return $a + $b;
    }
}

class Decorator {
    protected $decorable;
    
    function setDecoratable($obj) {
        $this->decoratable = $obj;
    }
    
    function add($a, $b) {
        return 2 + $this->decoratable->add($a, $b);
    }
}

$sample = new SampleBase();
$decorator = new Decorator();
$decorator->setDecoratable($sample);

$start = microtime(true);
for($i = 0; $i < 100000; $i++) {
    $decorator->add(2, 3);
}
$end = microtime(true);

echo 'Regular decorators: ' . $decorator->add(2, 3) . '. Duration:' . ($end - $start) . 's' . PHP_EOL;


/**
 * Stratum layers
 */
class Sample extends SampleBase implements Sirius\Stratum\LayerableInterface {
    use Sirius\Stratum\LayerableTrait;

    function add($a, $b) {
        return $this->executeLayeredMethod('add', func_get_args());
    }
    
}

class SampleLayer extends Sirius\Stratum\Layer {
    
    function add($a, $b) {
        return 2+$this->nextLayer->add($a, $b);
    }
    
}

class SampleWrapper extends Sirius\Stratum\Layer\ObjectWrapper {

    function add($a, $b) {
        return 1 + $this->object->callParentMethod('add', func_get_args());
    }

}

$stratum = new Sirius\Stratum\Manager;
$stratum->add('SampleLayer', 'Sample');

$sample = new Sample();
$sample->setTopLayer($stratum->createLayerStack($sample));

$start = microtime(true);
for($i = 0; $i < 100000; $i++) {
    $sample->add(2, 3);
}
$end = microtime(true);

echo 'Stratum layers: ' . $sample->add(2, 3) . '. Duration:' . ($end - $start) . 's' . PHP_EOL;

