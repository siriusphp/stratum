<?php
namespace Sirius\Stratum;

class Layer {
    protected $nextLayer;
    
    function __call($method, $args) {
        return $this->callNext($method, $args);
    }

    function setNextLayer(Layer $layer) {
        $this->nextLayer = $layer;
    }
    
    function callNext($method, $args) {
        return call_user_func_array(array($this->nextLayer, $method), $args);
    }
}