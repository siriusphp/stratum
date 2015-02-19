<?php
namespace Sirius\Stratum;

class ClassMaker
{

    /**
     * Create a class (layer/wrapper/child) for a layerable class
     *
     * @param string $class            
     * @param string $type
     *            (class|layer|wrapper)
     * @param string|null $destinationFolder            
     * @throws \InvalidArgumentException
     * @return string bool
     */
    public function makeForClass($class, $type, $destinationFolder = null)
    {
        $classBody = $this->createClassBody($class, $type);
        
        if ($destinationFolder !== null) {
            if (! is_writable($destinationFolder)) {
                throw new \InvalidArgumentException(sprintf('The %s folder is not writable', $destinationFolder));
            }
            
            // for Vendor\Package\ClassBase the destination is Class
            // for Vendor_Package_ClassBase the destination is Class
            // for VendorPackageClassBase the destination is VendorPackageClass
            if (strpos($class, '_') !== false) {
                $destinationClass = substr($class, (int) strrpos($class, '_'), - 4);
            } elseif (strpos($class, '\\') !== false) {
                $destinationClass = substr($class, (int) strrpos($class, '\\'), - 4);
            } else {
                $destinationClass = $class;
            }
            
            $destinationFile = rtrim($destinationFolder, DIRECTORY_SEPARATOR);
            $destinationFile .= DIRECTORY_SEPARATOR . $destinationClass . '.php';
            
            return file_put_contents($destinationFile, $classBody);
        }
        
        return $classBody;
    }

    protected function createClassBody($class, $type)
    {
        if (! class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class %s does not exist', $class));
        }
        
        if (substr($class, - 4) !== 'Base') {
            throw new \InvalidArgumentException('The class must end in `Base`');
        }
        
        $refClass = new \ReflectionClass($class);
        $namespace = $refClass->getNamespaceName();
        $baseClassName = $namespace ? substr($refClass->getName(), strlen($namespace) + 1) : $refClass->getName();
        $destinationClassName = substr($baseClassName, 0, - 4);
        $layerableMethods = array();
        $fileLines = file($refClass->getFileName());
        
        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (strpos($method->getDocComment(), '@Stratum\Layerable')) {
                $methodBody = implode(PHP_EOL, array_slice($fileLines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));
                $layerableMethods[] = array(
                    'head' => substr($methodBody, 0, strpos($methodBody, '{')),
                    'name' => $method->getName(),
                    'arguments' => implode(', ', array_map(function ($parameter)
                    {
                        return '$' . $parameter->getName();
                    }, $method->getParameters()))
                );
            }
        }
        
        if (! in_array($type, array(
            'class',
            'layer',
            'wrapper'
        ))) {
            throw new \InvalidArgumentException('Invalid type of class. Allowed values are `class`, `layer` and `wrapper`.');
        }
        
        ob_start();
        include_once __DIR__ . '/../templates/' . $type . '.php';
        $output = ob_get_clean();
        return $output;
    }
}