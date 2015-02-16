<?php 
echo '<?php' . PHP_EOL;

if ($namespace) { echo 'namespace ' . $namespace . ';' . PHP_EOL; }

?>

class <?php echo $destinationClassName; ?>Layer extends Sirius\Stratum\Layer
{

<?php foreach ($layerableMethods as $method) { ?>
    <?php echo trim($method['head']) . PHP_EOL; ?>
    {
        return $this->nextLayer-><?php echo $method['name']?>(<?php echo $method['arguments']?>);
    }

<?php } ?>
}