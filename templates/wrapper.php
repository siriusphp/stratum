<?php 
echo '<?php' . PHP_EOL;

if ($namespace) { echo 'namespace ' . $namespace . ';' . PHP_EOL; }

?>

class <?php echo $destinationClassName; ?>Wrapper extends Sirius\Stratum\Layer
{

<?php foreach ($layerableMethods as $method) { ?>
    <?php echo trim($method['head']) . PHP_EOL; ?>
    {
        return $this->object->callParentMethod('<?php echo $method['name']?>', func_get_args());
    }

<?php } ?>
}