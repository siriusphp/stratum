<?php
namespace Sirius\Decorators;

class Manager
{

    protected static $instance;

    protected $decoratorSets = array();

    protected $index = PHP_INT_MAX;

    static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    static function resetInstance() {
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
        
        $this->validateDecoratorArgument($classObjectOrCallback);
        
        foreach ($destinationClasses as $class) {
            if (! isset($this->decoratorSets[$class])) {
                $this->decoratorSets[$class] = array();
            }
            
            $this->decoratorSets[$class][] = array(
                'decorator' => $classObjectOrCallback,
                'priority' => $priority,
                'index' => $this->index --
            );
            
            $this->sortDecoratorSet($this->decoratorSets[$class]);
        }
    }

    function createDecoratorStack(DecoratableInterface $decoratableObject)
    {
        $class = get_class($decoratableObject);
    	$baseDecorator = new Decorator\ObjectWrapper($decoratableObject);
    	if (!isset($this->decoratorSets[$class])) {
    	    return $baseDecorator;
    	}
    	
    	foreach ($this->decoratorSets[$class] as $data) {
    	    $decorator = $this->createDecorator($data['decorator']);
    	    $decorator->setNextDecorator($baseDecorator);
    	    $baseDecorator = $decorator;
    	}
    	
    	return $baseDecorator;
    }
    
    /**
     * @param unknown $classObjectOrCallback
     * @throws \RuntimeException
     * @return \Sirius\Decorators\Decorator
     */
    protected function createDecorator($classObjectOrCallback)
    {
        if (is_string($classObjectOrCallback)) {
            return new $classObjectOrCallback;
        }
        
        if (is_callable($classObjectOrCallback)) {
            return call_user_func($classObjectOrCallback);
        }
        
        if (is_object($classObjectOrCallback)) {
            return clone($classObjectOrCallback);
        }
        
        throw new \RuntimeException('Cound not create decorator from the specifications');
    }

    protected function sortDecoratorSet($set)
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

    protected function validateDecoratorArgument($classObjectOrCallback)
    {
        if (is_object($classObjectOrCallback) && ! $classObjectOrCallback instanceof Decorator) {
            throw new \InvalidArgumentException('The decorator object must extend the Decorator class');
        }
        
        if (is_string($classObjectOrCallback) && ! class_exists($classObjectOrCallback)) {
            throw new \InvalidArgumentException('The decorator class does not exist');
        }
        
        if (is_string($classObjectOrCallback) && ! is_subclass_of($classObjectOrCallback, '\Sirius\Decorators\Decorator')) {
            throw new \InvalidArgumentException('The decorator class must extend the \Sirius\Decorators\Decorator class');
        }
        
        if (is_callable($classObjectOrCallback) && ! call_user_func($classObjectOrCallback) instanceof Decorator) {
            throw new \InvalidArgumentException('The callback generate a object of the \Sirius\Decorators\Decorator class');
        }
    }
}