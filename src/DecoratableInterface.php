<?php
namespace Sirius\Decorators;

interface DecoratableInterface {
    
    function callParentMethod($method, $args);
    
    function executeDecoratedMethod($method, $args);
}