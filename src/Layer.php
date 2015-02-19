<?php
namespace Sirius\Stratum;

class Layer {
    protected $nextLayer;
    
    /**
     * Method interceptor for when the layer doesn't implement a method of the wrapped object
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args) {
        return $this->callNext($method, $args);
    }

    /**
     * Set the next layer in the stack
     * 
     * @param Layer $layer
     */
    public function setNextLayer(Layer $layer) {
        $this->nextLayer = $layer;
    }
    
    /**
     * Call a method on the next layer
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected function callNext($method, $args) {
        return call_user_func_array(array($this->nextLayer, $method), $args);
    }
}