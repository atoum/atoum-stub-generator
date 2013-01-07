<?php

namespace Atoum\StubGenerator\Stub;

use \Symfony\Component\Filesystem\Filesystem;

class Test
{

    /**
     * @var string
     */
    private $classname;

    /**
     * @var array
     */
    private $asserters;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
     * @param string                                   $classname
     * @param array                                    $asserters
     */
    public function __construct(Filesystem $filesystem, $classname, array $asserters)
    {
        $this->filesystem = $filesystem;
        $this->classname  = $classname;
        $this->asserters  = $asserters;
    }

    /**
     * @param string $outputDir
     */
    public function generate($outputDir)
    {
        $this->filesystem->mkdir($outputDir);
        
        $test             = new $this->classname();
        $asserterLines    = array();
        $asserterAlias    = $test->getAsserterGenerator()->getAliases();
        $asserterAliasDoc = array();
        foreach ($this->asserters as $asserterName => $asserter) {
            $asserterLines[] = sprintf(
                '    /**
     * @var mixed $variable
     * 
     * @return asserters\%s
     */
    public function %s($variable) { }
                ',
                $asserter,
                $asserterName
            );

            if (false !== ($alias = array_search($asserterName, $asserterAlias))) {
                $asserterAliasDoc[] = sprintf('@method asserters\%s %s($variable)', $asserter, $alias);
            }
        }

        foreach (array_keys($test->getAssertionManager()->getHandlers()) as $handler) {
            $asserterAliasDoc[] = sprintf('@method test %s()', $handler);
        }

        $stubTemplate = '<?php
    
namespace atoum;

use atoum\asserters;

/**
 * %s
 */
class test
{

%s
}';

        file_put_contents(
            $outputDir . '/test.php',
            sprintf(
                $stubTemplate,
                implode("\n * ", $asserterAliasDoc),
                implode("\n", $asserterLines)
            )
        );
    }
}
