<?php

namespace Prophesizer\Generator\Item;

use Prophesizer\Generator\Render;

/**
 * Class ReturnValueItem
 * @package Prophesizer\Generator\Item
 */
class ReturnValueItem
{
    /**
     * @var string
     */
    private $baseType;
    /**
     * @var bool
     */
    private $isArray;
    /**
     * @var bool
     */
    private $isObject;
    /**
     * @var string|mixed|null
     */
    private $realType;

    /**
     * @return string
     */
    public function render()
    {
        $render = null;
        if ($this->isObject) {
            if ($this->skipClassProphecy($this->realType)) {
                $render = 'new '.$this->baseType.'()';
            } else {
                $render = '${{method}}{{_}}ReturnValue';
            }

            return '// return '.($this->isArray ? '[' : '') . $render . ($this->isArray ? ']' : '').';';
        }
        $sampleValue = $this->buildSampleValue($this->baseType, $this->isArray);

        return '// return '.$sampleValue.';';
    }

    /**
     * @return string
     */
    public function renderProphesized()
    {
        if (!$this->isObject || $this->skipClassProphecy($this->realType)) {
            return '';
        }

        $returnType = '\\'.ltrim($this->realType, '\\');
        $replacements = [
            'prophesizedReturnType' => $returnType,
        ];

        return Render::applyReplacements($this->getProphesizedTemplate(), $replacements);
    }

    /**
     * @param \ReflectionMethod $method
     * @param string[]          $use
     * @return ReturnValueItem[]
     */
    public static function extract(\ReflectionMethod $method, array $use)
    {
        $doc = $method->getDocComment();
        if (substr_count($doc, '@inheritdoc')) {
            try {
                $doc = $method->getPrototype()->getDocComment();
            } catch (\ReflectionException $e) {
                $doc = null;
            }
        }

        if ($doc) {
            return static::extractFromDocComment($doc, $use);
        }

        return [];
    }

    /**
     * ReturnValueItem constructor.
     * @param string   $type
     * @param string[] $use
     */
    private function __construct($type, array $use)
    {
        if ('[]' === substr($type, -2)) {
            $this->isArray = true;
            $this->baseType = substr($type, 0, -2);
        } elseif ('array' === $type) {
            $this->isArray = true;
            $this->baseType = null; // mixed
        } else {
            $this->isArray = false;
            $this->baseType = $type;
        }

        if (function_exists('\is_'.$this->baseType)) {
            $this->isObject = false;
            $this->realType = $this->baseType;
        } else {
            $this->isObject = true;
            $this->realType = isset($use[$this->baseType]) ? $use[$this->baseType] : $this->baseType;
        }
    }

    /**
     * @param string   $doc
     * @param string[] $use
     * @return ReturnValueItem[]
     */
    private static function extractFromDocComment($doc, array $use)
    {
        $return = [];
        $pattern = '#@return\s+([^;\s\n\r*]+)#i';
        if (preg_match($pattern, $doc, $matches)) {
            $returnTypes = explode('|', $matches[1]);
            foreach ($returnTypes as $returnType) {
                if (!$returnType || 'void' === $returnType) {
                    continue;
                }
                $return[] = new self($returnType, $use);
            }
        } else {
            $return[] = new self('null', $use); // void or nothing
        }

        return $return;
    }

    /**
     * @return string
     */
    private function getProphesizedTemplate()
    {
        $template = <<<'TEMPLATE'
        ${{method}}{{_}}ReturnValueProphecy = $this->prophesize('{{prophesizedReturnType}}');
        /** @var ${{method}}{{_}}ReturnValue {{prophesizedReturnType}} */
        ${{method}}{{_}}ReturnValue = ${{method}}{{_}}ReturnValueProphecy->reveal();
TEMPLATE;

        return $template;
    }

    private function buildSampleValue($baseType, $isArray)
    {
        switch ($baseType) {
            case 'int':
            case 'integer':
                $sampleValue = ($isArray ? '[-1000, 2000]' : '1000');
                break;
            case 'bool':
            case 'boolean':
                $sampleValue = ($isArray ? '[true, false]' : 'true');
                break;
            case 'string':
                $sampleValue = ($isArray ? "['str_array', 'str_of', 'str_strings']" : "'single_string'");
                break;
            case 'float':
            case 'double':
            case 'real':
                $sampleValue = ($isArray ? "[-1.1, 0.02, 1000.99]" : "0.1");
                break;
            case 'array':
                $sampleValue = "['array_of_mixed_types', -0.001, null, ['foo']]";
                break;
            case 'null':
                $sampleValue = "null";
                break;
            default:
                $sampleValue = 'null; // void';
                break;
        }
        
        return $sampleValue;
    }

    /**
     * @see http://php.net/manual/en/reserved.classes.php
     * @param string $className
     * @return bool
     */
    private function skipClassProphecy($className)
    {
        $className = '\\'.ltrim($className, '\\');
        $skip = [
            '\DateTime',
            '\stdClass',
            '\Exception',
            '\ErrorException',
            '\php_user_filter',
            '\Closure',
            '\Generator',
        ];

        return in_array($className, $skip);
    }
}
