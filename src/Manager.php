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

    /**
     * This is used to differenciate between layers
     * with the same priority
     * 
     * @var int
     */
    protected $index = PHP_INT_MAX;

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
    public function add($classObjectOrCallback, $targets, $priority = 0)
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

    /**
     * Compiles the set of layers that match an object
     * 
     * @param object $object
     * @return array
     */
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

    /**
     * Checks if a layer is fit for an object
     * 
     * @param array $layer
     * @param object $object
     * @return boolean
     */
    protected function isLayerFitForObject($layer, $object)
    {
        switch ($layer['type']) {
            case 'implements':
                return in_array($layer['target'], class_implements($object));
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
            case 'is':
                return get_class($object) === $layer['target'];
        }
        return false;
    }

    /**
     * Creates the layer stack for an object
     * 
     * @param LayerableInterface $layerableObject
     * @return \Sirius\Stratum\Layer
     */
    public function createLayerStack(LayerableInterface $layerableObject)
    {
        $layerableObjectClassWrapper = get_class($layerableObject) . 'Wrapper';
        if (class_exists($layerableObjectClassWrapper)) {
            $baseLayer = new $layerableObjectClassWrapper($layerableObject);
        } else {
            $baseLayer = new Layer\ObjectWrapper($layerableObject);
        }
        
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
     * Creates a layer object based on its definition
     * 
     * @param mixed $classObjectOrCallback            
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

    /**
     * Compares the specs of 2 layers based on the priority
     * 
     * @param array $e1
     * @param array $e2
     * @return number
     */
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

    /**
     * Ensures that the layer argument is valid
     * 
     * @param mixed $classObjectOrCallback
     * @throws \InvalidArgumentException
     */
    protected function validateLayerArgument($classObjectOrCallback)
    {
        if (is_object($classObjectOrCallback) && ! $classObjectOrCallback instanceof Layer) {
            throw new \InvalidArgumentException('The decorator object must extend the \Sirius\Stratum\Layer class');
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