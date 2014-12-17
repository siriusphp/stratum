<?php
namespace Sirius\Decorators;

use Sirius\Decorators\Manager as DecoratorManager;

trait DecoratableTrait
{

    /**
     *
     * @var Decorator
     */
    protected $topDecorator;

    function callParentMethod($method, $args)
    {
        return call_user_func_array('parent::' . $method, $args);
    }

    function executeDecoratedMethod($method, $args)
    {
        if (! $this->topDecorator) {
            $this->topDecorator = DecoratorManager::getInstance()->createDecoratorStack($this);
        }
        return call_user_func_array(array($this->topDecorator, $method), $args);
    }
}