<?php


$decoratorManager = DecoratorManager::getInstance();
$decoratorManager->add($classObjectOrCallback, $destinationClasses, $priority);

trait DecoratableTrait {
    
    function callParentMethod($method, $args) {
        return call_user_func_array('parent::' . $method, $args);
    }
    
    function executeDecoratedMethod($method, $args) {
        $stack = DecoratorManager::getInstance()->getStackForObject($this);
        return $stack->execute($this, $method, $args);
    }
}

class DecoratedClassBase {
    
    function method() {
        
    }
}

class DecoratedClass {
    use DecoratableTrait;
    
    function method() {
        return $this->executeDecoratedMethod(__FUNCTION__, func_get_args());
    }
}