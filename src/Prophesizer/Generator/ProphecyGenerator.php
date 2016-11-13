<?php

namespace Prophesizer\Generator;

use Prophesizer\Source\SourceFileLine;

/**
 * Class ProphecyGenerator
 */
class ProphecyGenerator
{
    /** @var SourceFileLine */
    private $sourceFileLine;

    /** @var string */
    private $code;

    /** @var  DoubleFactoryMethod */
    private $doubleFactoryMethod;

    /**
     * ProphecyGenerator constructor.
     * @param $sourceFileLine
     */
    public function __construct(SourceFileLine $sourceFileLine)
    {
        $this->sourceFileLine = $sourceFileLine;
    }

    public function getDoubleCode()
    {
        if (!$this->code) {
            $this->code = $this->render();
        }

        return $this->code;
    }

    /**
     * @return string
     */
    public function getDoubleMethodName()
    {
        return $this->getDoubleFactoryMethod()->getDoubleMethodName();
    }

    /**
     * @param SourceFileLine $sourceFileLine
     * @return DoubleFactoryMethod
     */
    private function process(SourceFileLine $sourceFileLine)
    {
        $classReflection = new \ReflectionClass($sourceFileLine->getClassName());
        $use = $this->extractUseSection($classReflection->getFileName());

        $methods = [];
        foreach ($classReflection->getMethods() as $method) {
            if ($method->isConstructor()) {
                continue;
            }
            if ($method->isPublic() && !$method->isStatic()) {
                $methods[] = new MethodProphecy($method, $use);
            }
        }
        $doubleFactoryMethod = new DoubleFactoryMethod($sourceFileLine->getClassName(), $methods);

        return $doubleFactoryMethod;
    }

    /**
     * @param string $fileName
     * @return string[]
     */
    private function extractUseSection($fileName)
    {
        $use = [];
        $lines = file($fileName, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ('use ' === substr($line, 0, 4)) {
                $line = preg_split('#\s+#', rtrim($line, ';'));
                if (2 === count($line)) {
                    // use \DateTime;
                    $alias = explode('\\', $line[1]);
                    $alias = end($alias);
                    $use[$alias] = $line[1];
                } elseif (4 === count($line)) {
                    $alias = $line[3]; // use \DateTime as DT;
                    $use[$alias] = $line[1];
                }
            }
        }

        return $use;
    }

    /**
     * @return string
     */
    private function render()
    {
        return $this->getDoubleFactoryMethod()->render();
    }

    /**
     * @return DoubleFactoryMethod
     */
    private function getDoubleFactoryMethod()
    {
        if (!$this->doubleFactoryMethod) {
            $this->doubleFactoryMethod = $this->process($this->sourceFileLine);
        }

        return $this->doubleFactoryMethod;
    }
}
