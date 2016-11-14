<?php

namespace Prophesizer\Generator\Item;

/**
 * Class ParameterItem
 * @package Prophesizer\Generator\Item
 */
class ParameterItem
{
    /** @var string */
    private $name;

    /** @var array|string[] */
    private $types;

    /** @var array|string[] */
    private $use;

    /**
     * ParameterItem constructor.
     * @param string   $name
     * @param string[] $types
     * @param string[] $use
     */
    public function __construct($name, array $types, array $use)
    {
        $this->name = $name;
        $this->types = $types;
        $this->use = $use;
    }

    /**
     * @param \ReflectionMethod $method
     * @param string[]          $use
     * @return ParameterItem[]
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
     * @param string   $doc
     * @param string[] $use
     * @return ParameterItem[]
     */
    private static function extractFromDocComment($doc, array $use)
    {
        $return = [];
        $pattern = '#@param\s+(([^\s]+)\s+?)?(\$[a-z_][a-z_0-9]*)#i';
        if (preg_match_all($pattern, $doc, $matches)) {
            // $matches[2] - type(s) column; $matches[3] - param column
            $paramToType = array_combine($matches[3], $matches[2]);
            foreach ($paramToType as $paramName => $paramTypes) {
                $paramTypes = $paramTypes ? explode('|', $paramTypes) : ['mixed'];
                $return[] = new self($paramName, $paramTypes, $use);
            }
        }

        return $return;
    }

    public function render()
    {
        if (1 === count($this->types)) {
            return $this->renderOneType(end($this->types));
        } else {
            $variants = [];
            foreach ($this->types as $type) {
                $variants[] = $this->renderOneType($type);
            }

            return 'Argument::allOf('.implode(', ', $variants).')';
        }
    }

    private function renderOneType($type)
    {
        if (isset($this->use['type'])) {
            $type = '\\'.ltrim($this->use['type'], '\\');
        } elseif (strtolower($type) === $type) {
            // +is_{type}() function exists = built-in type?
        }

        switch ($type) {
            case 'string[]':
                $method = 'that(function ($a) { return ($a === array_filter($a, \'is_string\')); })';
                break;
            case 'int[]':
            case 'integer[]':
                $method = 'that(function ($a) { return ($a === array_filter($a, \'is_int\')); })';
                break;
            case 'float[]':
                $method = 'that(function ($a) { return ($a === array_filter($a, \'is_float\')); })';
                break;
            case 'bool[]':
            case 'boolean[]':
                $method = 'that(function ($a) { return ($a === array_filter($a, \'is_bool\')); })';
                break;
            case 'mixed':
                $method = 'any()';
                break;
            case 'false':
                $method = 'is(false)';
                break;
            case 'true':
                $method = 'is(true)';
                break;
            default:
                $method = 'type(\''.$type.'\')';
                break;
        }

        return $this->getArgumentClassAlias().'::'.$method;
    }

    /**
     * @return string
     */
    private function getArgumentClassAlias()
    {
        return $this->useShortArgumentAlias() ? 'A' : 'Argument';
    }

    /**
     * @return bool
     */
    private function useShortArgumentAlias()
    {
        return false;
    }
}
