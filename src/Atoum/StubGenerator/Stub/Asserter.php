<?php

namespace Atoum\StubGenerator\Stub;

use Symfony\Component\Filesystem\Filesystem;

class Asserter
{

    /**
     * @var string
     */
    private $classname;

    /**
     * @var string
     */
    private $asserterName;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
     * @param string                                   $asserterName
     * @param string                                   $classname
     */
    public function __construct(Filesystem $filesystem, $asserterName, $classname)
    {
        $this->filesystem   = $filesystem;
        $this->asserterName = $asserterName;
        $this->classname    = $classname;
    }

    /**
     * @param string $outputDir
     *
     * @throws \InvalidArgumentException
     */
    public function generate($outputDir)
    {
        $this->filesystem->mkdir($outputDir);
        
        $reflection = new \ReflectionClass($this->classname);

        if (!$reflection->isSubclassOf(
            '\mageekguy\atoum\asserter'
        ) && $this->classname !== '\mageekguy\atoum\asserter'
        ) {
            throw new \InvalidArgumentException(sprintf(
                '%s is not a \mageekguy\atoum\asserter object',
                $this->classname
            ));
        }

        $parent = $reflection->getParentClass();

        $methodStubs = array();

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (($parent !== false && $parent->hasMethod($method->getName())) || $method->getDeclaringClass(
            ) != $reflection
            ) {
                continue;
            }

            $methodStubs[] = $this->buildMethodStub($method);
        }

        file_put_contents(
            $outputDir . $this->asserterName . '.php',
            $this->buildClassStub($reflection, $methodStubs, $parent)
        );
    }

    /**
     * @param \ReflectionClass         $class
     * @param array                    $methodStubs
     * @param \ReflectionClass|boolean $parent
     *
     * @return string
     */
    private function buildClassStub(\ReflectionClass $class, array $methodStubs, $parent = false)
    {
        $stubTemplate              = '<?php
    
namespace %s;

class %s %s
{

%s
}';
        $classNameWithoutNamespace = str_replace($class->getNamespaceName() . '\\', '', $class->getName());

        return sprintf(
            $stubTemplate,
            $this->cleanNamespace($class->getNamespaceName()),
            $classNameWithoutNamespace,
            $parent !== false ? 'extends \\' . $this->cleanNamespace($parent->getName()) : '',
            implode("", $methodStubs)
        );
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return string
     */
    private function buildMethodStub(\ReflectionMethod $method)
    {
        $methodStubTemplate = '
    /**
     * %s
     * 
     * @return \%s
     */
    public function %s(%s) {} 
';

        $arguments       = array();
        $argumentsPhpDoc = array();
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                $default    = $parameter->getDefaultValue();
                $strDefault = 'null';
                if ($default === '') {
                    $strDefault = "''";
                } elseif (true === $default) {
                    $strDefault = "true";
                } elseif (false === $default) {
                    $strDefault = "false";
                }

                $arguments[] = sprintf('$%s = %s', $parameter->getName(), $strDefault);
            } else {
                $arguments[] = sprintf('$%s', $parameter->getName());
            }

            $argumentsPhpDoc[] = sprintf("@var mixed $%s", $parameter->getName());
        }

        return sprintf(
            $methodStubTemplate,
            implode("\n     * ", $argumentsPhpDoc),
            $this->cleanNamespace($method->getDeclaringClass()->getName()),
            $method->getName(),
            implode(', ', $arguments)
        );
    }

    /**
     * @param string $namespace
     *
     * @return string
     */
    private function cleanNamespace($namespace)
    {
        return str_replace('mageekguy\\', '', $namespace);
    }
}
