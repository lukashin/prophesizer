<?php

namespace Prophesizer\Generator;

use Prophesizer\Generator\Item\ParameterItem;
use Prophesizer\Generator\Item\ReturnValueItem;
use Prophesizer\Generator\Item\ThrowExceptionItem;

/**
 * Class MethodProphecy
 */
class MethodProphecy
{
    /** @var \ReflectionMethod */
    private $reflectionMethod;

    /** @var string[] */
    private $use;

    /** @var ParameterItem[] */
    private $params = null;

    /** @var ThrowExceptionItem[] */
    private $throws;

    /** @var ReturnValueItem[] */
    private $returns;

    /**
     * MethodProphecy constructor.
     * @param \ReflectionMethod $reflectionMethod
     * @param string[]          $use
     */
    public function __construct(\ReflectionMethod $reflectionMethod, array $use)
    {
        $this->reflectionMethod = $reflectionMethod;
        $this->use = $use;
        //echo $this->reflectionMethod->getDeclaringClass()->getFileName().PHP_EOL;
        $this->process($this->reflectionMethod);
    }

    /**
     * @param \ReflectionMethod $method
     */
    private function process(\ReflectionMethod $method)
    {
        $this->params = ParameterItem::extract($method, $this->use);
        $this->returns = ReturnValueItem::extract($method, $this->use);
        $this->throws = ThrowExceptionItem::extract($method, $this->use);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->reflectionMethod->getName();
    }

    /** @return string */
    public function render()
    {
        $prophesizedSamples = $this->renderProphesizedReturnValuesSamples($this->returns);
        $returns = $this->renderReturns($this->returns);
        $throws = $this->renderThrowExceptionItems($this->throws);
        $parameters = $this->renderMethodParameters($this->params);

        $replacements = [
            'prophesizedReturnValueSamples' => $prophesizedSamples,
            'useProphesizedReturnValue' => ($prophesizedSamples ? ' use (${{method}}{{_}}ReturnValue) ' : ' '),
            'throws' => $throws,
            'returns' => ($throws ? PHP_EOL : '').$returns,
            'parameters' => $parameters,
            'method' => lcfirst($this->getName()),
        ];

        return Render::applyReplacements($this->getTemplate(), $replacements);
    }

    /**
     * @param ReturnValueItem[] $returns
     * @return string
     */
    private function renderProphesizedReturnValuesSamples($returns)
    {
        $render = [];
        foreach ($returns as $return) {
            $renderProphesized = $return->renderProphesized(); // empty if return type is not class/interface
            if ($renderProphesized) {
                $render[] = $renderProphesized;
            }
        }

        return implode(PHP_EOL, $render);
    }

    /**
     * @param ReturnValueItem[] $returns
     * @return string
     */
    private function renderReturns($returns)
    {
        $render = [];
        foreach ($returns as $return) {
            $render[] = str_repeat(' ', 4*4) . $return->render();
        }

        return implode(PHP_EOL, $render);
    }

    /**
     * @param ThrowExceptionItem[] $throws
     * @return string
     */
    private function renderThrowExceptionItems($throws)
    {
        $render = [];
        foreach ($throws as $throw) {
            $render[] = str_repeat(' ', 4*4) . $throw->render();
        }

        return implode(PHP_EOL, $render);
    }

    /**
     * @param ParameterItem[] $params
     * @return string
     */
    private function renderMethodParameters($params)
    {
        $render = [];
        foreach ($params as $param) {
            $render[] = $param->render();
        }
        $oneLine = implode(', ', $render);
        if (strlen($oneLine . $this->getName()) <= 100) {
            return $oneLine;
        }
        foreach ($render as &$parameterRender) {
            $parameterRender = str_repeat(' ', 4*4) . $parameterRender;
        }
        unset($parameterRender);

        return PHP_EOL . implode(',' . PHP_EOL, $render) . PHP_EOL . str_repeat(' ', 4*3);
    }

    /**
     * @return string
     */
    private function getTemplate()
    {
        $template = <<<'TEMPLATE'
{{prophesizedReturnValueSamples}}
        /** @noinspection PhpUndefinedMethodInspection */
        {{prophecyVar}}
            ->{{method}}({{parameters}})
            ->will(function (array $args){{useProphesizedReturnValue}}{
                // todo: modify generated method double
{{throws}}{{returns}}
            })
            ->shouldBeCalled();
            //->shouldNotBeCalled();
TEMPLATE;

        return $template;
    }
}
