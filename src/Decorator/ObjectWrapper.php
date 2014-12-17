<?php
namespace Sirius\Decorators\Decorator;

use Sirius\Decorators\Decorator;
use Sirius\Decorators\DecoratableInterface;

/**
 * This class is used as the bottom of the decorator stack
 * It will make calls on the decoratable object instead of on the next decorator 
 */
class ObjectWrapper extends Decorator
{
    var $object;
    
    function __construct(DecoratableInterface $object) {
        $this->object = $object;
    }
    
    function callNext($method, $args) {
        return $this->object->callParentMethod($method, $args);
    }
} 