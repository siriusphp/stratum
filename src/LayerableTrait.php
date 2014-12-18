<?php
namespace Sirius\Stratum;

use Sirius\Stratum\Manager as StratumManager;

trait LayerableTrait
{

    /**
     *
     * @var Layer
     */
    protected $topLayer;

    function callParentMethod($method, $args)
    {
        return call_user_func_array('parent::' . $method, $args);
    }

    function executeLayeredMethod($method, $args)
    {
        if (! $this->topLayer) {
            $this->topLayer = StratumManager::getInstance()->createLayerStack($this);
        }
        return call_user_func_array(array($this->topLayer, $method), $args);
    }
}