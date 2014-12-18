<?php
namespace Sirius\Stratum;

class Manager
{

    protected static $instance;

    protected $layerSets = array();

    protected $index = PHP_INT_MAX;

    static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    static function resetInstance()
    {
        self::$instance = null;
        return self::getInstance();
    }

    function add($classObjectOrCallback, $destinationClasses, $priority = 0)
    {
        if (is_string($destinationClasses)) {
            $destinationClasses = array(
                $destinationClasses
            );
        }
        
        $this->validateLayerArgument($classObjectOrCallback);
        
        foreach ($destinationClasses as $class) {
            if (! isset($this->layerSets[$class])) {
                $this->layerSets[$class] = array();
            }
            
            $this->index--;
            $this->layerSets[$class][] = array(
                'decorator' => $classObjectOrCallback,
                'priority' => $priority,
                'index' => $this->index
            );
            
            $this->sortLayerSet($this->layerSets[$class]);
        }
    }

    function createLayerStack(LayerableInterface $layerableObject)
    {
        $class = get_class($layerableObject);
        $baseLayer = new Layer\ObjectWrapper($layerableObject);
        if (! isset($this->layerSets[$class])) {
            return $baseLayer;
        }
        
        foreach ($this->layerSets[$class] as $data) {
            $layer = $this->createLayer($data['decorator']);
            $layer->setNextLayer($baseLayer);
            $baseLayer = $layer;
        }
        
        return $baseLayer;
    }

    /**
     *
     * @param unknown $classObjectOrCallback            
     * @throws \RuntimeException
     * @return \Sirius\Stratum\Layer
     */
    protected function createLayer($classObjectOrCallback)
    {
        if (is_string($classObjectOrCallback)) {
            return new $classObjectOrCallback();
        }
        
        if (is_callable($classObjectOrCallback)) {
            return call_user_func($classObjectOrCallback);
        }
        
        if (is_object($classObjectOrCallback)) {
            return clone ($classObjectOrCallback);
        }
        
        throw new \RuntimeException('Cound not create layer from the specifications');
    }

    protected function sortLayerSet($set)
    {
        return usort($set, array(
            $this,
            'setItemsComparator'
        ));
    }

    protected function setItemsComparator($e1, $e2)
    {
        // first check the user provided priority
        if ($e1['priority'] > $e2['priority']) {
            return - 1;
        } elseif ($e1['priority'] < $e2['priority']) {
            return 1;
        }
        // then check the automaticly assigned index
        if ($e1['index'] > $e2['index']) {
            return - 1;
        } elseif ($e1['index'] < $e2['index']) {
            return 1;
        }
        return 0;
    }

    protected function validateLayerArgument($classObjectOrCallback)
    {
        if (is_object($classObjectOrCallback) && ! $classObjectOrCallback instanceof Layer) {
            throw new \InvalidArgumentException('The decorator object must extend the Decorator class');
        }
        
        if (is_string($classObjectOrCallback) && ! class_exists($classObjectOrCallback)) {
            throw new \InvalidArgumentException('The decorator class does not exist');
        }
        
        if (is_string($classObjectOrCallback) && ! is_subclass_of($classObjectOrCallback, '\Sirius\Stratum\Layer')) {
            throw new \InvalidArgumentException('The decorator class must extend the \Sirius\Stratum\Layer class');
        }
        
        if (is_callable($classObjectOrCallback) && ! call_user_func($classObjectOrCallback) instanceof Layer) {
            throw new \InvalidArgumentException('The callback generate a object of the \Sirius\Stratum\Layer class');
        }
    }
}