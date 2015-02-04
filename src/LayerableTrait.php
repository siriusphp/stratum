<?php
namespace Sirius\Stratum;

use Sirius\Stratum\Manager as StratumManager;

trait LayerableTrait
{

    /**
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
    function callParentMethod($method, $args = array())
    {
        return call_user_func_array('parent::' . $method, $args);
    }

    /**
     * This will call the proper method on the top layer
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected function executeLayeredMethod($method, $args = array())
    {
        if (! $this->topLayer) {
            $this->topLayer = StratumManager::getInstance()->createLayerStack($this);
        }
        return call_user_func_array(array($this->topLayer, $method), $args);
    }
}