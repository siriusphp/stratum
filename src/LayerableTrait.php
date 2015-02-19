<?php
namespace Sirius\Stratum;

use Sirius\Stratum\Layer;

trait LayerableTrait
{

    /**
     *
     * @var Layer
     */
    protected $topLayer;

    /**
     * This is required by the bottom layer (ObjectWrapper) so that parent methods
     * are executed (they contain the business logic code)
     *
     * @param string $method            
     * @param array $args            
     * @return mixed
     */
    public function callParentMethod($method, $args = array())
    {
        return call_user_func_array('parent::' . $method, $args);
    }

    /**
     * Set the top layer of this object
     *
     * @param Layer $topLayer            
     */
    public function setTopLayer(Layer $topLayer)
    {
        $this->topLayer = $topLayer;
    }

    /**
     * This will call the proper method on the top layer
     *
     * @param string $method            
     * @param array $args            
     * @return mixed
     */
    public function executeLayeredMethod($method, $args = array())
    {
        if (! $this->topLayer) {
            return $this->callParentMethod($method, $args);
        }
        return call_user_func_array(array(
            $this->topLayer,
            $method
        ), $args);
    }
}