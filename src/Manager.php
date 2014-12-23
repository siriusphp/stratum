<?php
namespace Sirius\Stratum;

class Manager
{

    protected static $instance;

    /**
     * Layers definitions as attached to the manager
     * 
     * @var array
     */
    protected $layers = array();

    /**
     * Layer sets based on the class of the object
     * 
     * @var array
     */
    protected $layerSets = array();

    protected $index = PHP_INT_MAX;

    /**
     *
     * @return \Sirius\Stratum\Manager
     */
    static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * For testing purposes (when you need the layers to be reconfigured)
     * 
     * @return \Sirius\Stratum\Manager
     */
    static function resetInstance()
    {
        self::$instance = null;
        return self::getInstance();
    }

    /**
     * Add a layer to the stratum manager
     * $target can be:
     * 1) a string representing:
     * - a class: '\Some\Class'
     * - an interface: 'implements:\Some\Interface'
     * - an base class: 'extends:\Some\BaseClass'
     * - a trait: 'uses:\SomeTrait'
     * 2) comma separated strings from 1)
     * 3) an array of strings from 1)
     *
     * @param mixed $classObjectOrCallback            
     * @param string|array $targets            
     * @param number $priority            
     */
    function add($classObjectOrCallback, $targets, $priority = 0)
    {
        if (is_string($targets)) {
            $targets = explode(',', $targets);
        }
        
        $this->validateLayerArgument($classObjectOrCallback);
        $this->layerSets = array(); // reset the cache
        
        foreach ($targets as $target) {
            $this->index --;
            $type = 'is'; // default type for the target
            if (strpos($target, ':') !== false) {
                list ($type, $target) = explode(':', $target, 2);
            }
            if (! in_array($type, array(
                'is',
                'implements',
                'extends',
                'uses'
            ))) {
                throw new \InvalidArgumentException('The type of target for "' . $target . '" is not valid. Probably you misspelled something');
            }
            $this->layers[] = array(
                'decorator' => $classObjectOrCallback,
                'type' => trim($type),
                'target' => trim($target),
                'priority' => $priority,
                'index' => $this->index
            );
        }
    }

    protected function getLayerSetForObject($object)
    {
        $class = get_class($object);
        if (isset($this->layerSets[$class])) {
            return $this->layerSets[$class];
        }
        
        $this->layerSets[$class] = array();
        
        foreach ($this->layers as $layer) {
            if ($this->isLayerFitForObject($layer, $object)) {
                $this->layerSets[$class][] = $layer;
            }
        }
        usort($this->layerSets[$class], array(
            $this,
            'layerSetComparator'
        ));
        return $this->layerSets[$class];
    }

    protected function isLayerFitForObject($layer, $object)
    {
        switch ($layer['type']) {
            case 'implements':
                return in_array($layer['target'], class_implements($object));
                break;
            case 'uses':
                // test the parent classes as well use the targeted trait
                $targetClasses = class_parents($object);
                array_unshift($targetClasses, get_class($object));
                foreach ($targetClasses as $class) {
                    if (in_array($layer['target'], class_uses($class))) {
                        return true;
                    }
                }
                break;
            case 'extends':
                return is_a($object, $layer['target']);
                break;
            case 'is':
                return get_class($object) === $layer['target'];
                break;
        }
        return false;
    }

    function createLayerStack(LayerableInterface $layerableObject)
    {
        $baseLayer = new Layer\ObjectWrapper($layerableObject);
        
        $layers = $this->getLayerSetForObject($layerableObject);
        if (empty($layers)) {
            return $baseLayer;
        }
        
        foreach ($layers as $data) {
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

    protected function layerSetComparator($e1, $e2)
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