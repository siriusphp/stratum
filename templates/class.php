<?php 
echo '<?php' . PHP_EOL;

if ($namespace) { echo 'namespace ' . $namespace . ';' . PHP_EOL; }

?>

class <?php echo $destinationClassName; ?> extends <?php echo $baseClassName . PHP_EOL; ?>
{
    use Sirius\Stratum\LayerableTrait;

<?php foreach ($layerableMethods as $method) { ?>
    <?php echo trim($method['head']) . PHP_EOL; ?>
    {
        if ($this->topLayer) {
            return $this->topLayer-><?php echo $method['name']?>(<?php echo $method['arguments']?>);
        }
        return parent::<?php echo $method['name']?>(<?php echo $method['arguments']?>);
    }

<?php } ?>
}