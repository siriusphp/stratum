<?php
namespace Sirius\Stratum\Layer;

use Sirius\Stratum\Layer;
use Sirius\Stratum\LayerableInterface;

/**
 * This class is used as the bottom of the layer stack
 * It will make calls on the layerable object instead of on the next layer 
 */
class ObjectWrapper extends Layer
{
    protected $object;
    
    function __construct(LayerableInterface $object) {
        $this->object = $object;
    }
    
    protected function callNext($method, $args) {
        return $this->object->callParentMethod($method, $args);
    }
} 