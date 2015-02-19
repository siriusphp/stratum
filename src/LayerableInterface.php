<?php
namespace Sirius\Stratum;

interface LayerableInterface {
    /**
     * This is required by the bottom layer (ObjectWrapper) so that parent methods
     * are executed (they contain the business logic code)
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function callParentMethod($method, $args = array());
    /**
     * This will call the proper method on the top layer
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function executeLayeredMethod($method, $args = array());
}