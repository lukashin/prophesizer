<?php

namespace Prophesizer\Generator;

/**
 * Class DoubleFactoryMethod
 */
class DoubleFactoryMethod
{
    /** @var string */
    private $returnType;

    /** @var MethodProphecy[] */
    private $methodProphecyCollection;

    /** @var string|null */
    private $doubleMethodName = null;

    /**
     * @param string           $returnType
     * @param MethodProphecy[] $methods
     */
    public function __construct($returnType, array $methods)
    {
        $this->returnType = $returnType;
        $this->methodProphecyCollection = $methods;
    }

    /**
     * @return string
     */
    public function getDoubleMethodName()
    {
        if (!$this->doubleMethodName) {
            $this->doubleMethodName = 'get'.$this->getShortReturnType().'Double';
        }

        return $this->doubleMethodName;
    }

    /**
     * @param bool $useSnakeSeparator Generate $method__Prophecy
     * @return string
     */
    public function render($useSnakeSeparator = true)
    {
        $ShortReturnType = $this->getShortReturnType();

        $replacements = [
            'getDoubleMethod' => $this->getDoubleMethodName(),
            'methods' => $this->renderMethods($this->methodProphecyCollection),
            'prophecyVar' => '${{shortReturnType}}Prophecy',
            'returnType' => $this->returnType,
            'shortReturnType' => lcfirst($ShortReturnType),
            'ShortReturnType' => $ShortReturnType,
            'ReturnType' => ucfirst($this->returnType),
            '_' => $useSnakeSeparator ? '__' : '',
        ];

        return Render::applyReplacements($this->getTemplate(), $replacements);
    }

    /**
     * @return string
     */
    private function getShortReturnType()
    {
        $ShortReturnType = explode('\\', trim($this->returnType, '\\'));

        return end($ShortReturnType);
    }

    /**
     * @return string
     */
    private function getTemplate()
    {
        $template = <<<'TEMPLATE'
    /**
     * @return {{returnType}}
     */
    private function {{getDoubleMethod}}() {
        {{prophecyVar}} = $this->prophesize('{{returnType}}');

{{methods}}

        return {{prophecyVar}}->reveal();
    }
TEMPLATE;

        return $template;
    }

    /**
     * @param MethodProphecy[] $methodProphecyCollection
     * @return string
     */
    private function renderMethods(array $methodProphecyCollection)
    {
        $output = [];
        /** @var MethodProphecy $methodProphecy */
        foreach ($methodProphecyCollection as $methodProphecy) {
            $output[] = $methodProphecy->render();
        }
        $output = implode(PHP_EOL . PHP_EOL, $output);

        return $output;
    }
}
