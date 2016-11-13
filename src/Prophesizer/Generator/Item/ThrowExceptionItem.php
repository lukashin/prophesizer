<?php

namespace Prophesizer\Generator\Item;

class ThrowExceptionItem
{
    /** @var string */
    private $exceptionClass;

    /**
     * ThrowExceptionItem constructor.
     * @param string $exceptionClass
     */
    public function __construct($exceptionClass)
    {
        $this->exceptionClass = $exceptionClass;
    }

    /**
     * @param \ReflectionMethod $method
     * @param string[]          $use
     * @return ThrowExceptionItem[]
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
     * @see https://phpdoc.org/docs/latest/references/phpdoc/tags/throws.html
     * @param string $doc
     * @param array  $use
     * @return ThrowExceptionItem[]
     */
    private static function extractFromDocComment($doc, array $use)
    {
        $return = [];
        $pattern = '#@throws\s+([^;\s]+)#i';
        if (preg_match_all($pattern, $doc, $matches)) {
            foreach ($matches[1] as $exceptionClass) {
                if (isset($use[$exceptionClass])) {
                    $exceptionClass = '\\' . ltrim($use[$exceptionClass], '\\');
                }
                $return[] = new self($exceptionClass);
            }
        }

        return $return;
    }

    public function render()
    {
        return sprintf('// throw new %s(\'Thrown in {{ShortReturnType}}::{{method}}()\');', $this->exceptionClass);
    }
}
