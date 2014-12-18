<?php
namespace Sirius\Stratum;

interface LayerableInterface {
    
    function callParentMethod($method, $args);
    
    function executeLayeredMethod($method, $args);
}