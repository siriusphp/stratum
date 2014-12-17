<?php
namespace Sirius\Decorators;

class Decorator {
    protected $nextDecorator;
    
    function __call($method, $args) {
        return $this->callNext($method, $args);
    }

    function setNextDecorator(Decorator $decorator) {
        $this->nextDecorator = $decorator;
    }
    
    function callNext($method, $args) {
        return call_user_func_array(array($this->nextDecorator, $method), $args);
    }
}